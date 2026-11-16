<?php

declare(strict_types=1);

namespace Masilia\Bundle\IbexaSites\Command;

use Masilia\Contracts\IbexaSites\SiteInvalid;
use Masilia\Contracts\IbexaSites\SiteNotFound;
use Masilia\Contracts\IbexaSites\SiteRegistry;
use Masilia\Contracts\IbexaSites\SiteStatus;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Serves sites of the registry (or stops serving them) from the command line: what a deployment does once the YAML has let go of
 * the sites that were taken over, with the same rules as the admin (a site is checked again, the Anonymous role is updated).
 */
#[AsCommand(name: 'masilia:sites:set-status', description: 'Activate or disable sites of the registry')]
final class SetSiteStatusCommand extends Command
{
    public function __construct(private readonly SiteRegistry $registry)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('status', InputArgument::REQUIRED, '"active" or "disabled"')
            ->addArgument('identifiers', InputArgument::IS_ARRAY, 'The sites (their group name: "acme_group")')
            ->addOption('all', null, InputOption::VALUE_NONE, 'Every site of the registry that is not in that status yet');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $status = SiteStatus::tryFrom((string) $input->getArgument('status'));
        if ($status === null || $status === SiteStatus::Draft) {
            $io->error('The status is "active" or "disabled".');

            return Command::INVALID;
        }
        $identifiers = array_values(array_map('strval', (array) $input->getArgument('identifiers')));
        if ((bool) $input->getOption('all')) {
            $identifiers = array_values(array_map(
                static fn ($site): string => $site->identifier,
                array_filter($this->registry->list(), static fn ($site): bool => $site->status !== $status),
            ));
        }
        if ($identifiers === []) {
            $io->warning('No site to change: name them, or use --all.');

            return Command::SUCCESS;
        }

        $failed = false;
        foreach ($identifiers as $identifier) {
            try {
                $site = $this->registry->setStatus($identifier, $status);
                $io->writeln(sprintf(' <info>%s</info> is %s', $site->identifier, $site->status->value));
            } catch (SiteInvalid $invalid) {
                $failed = true;
                $io->writeln(sprintf(' <error>%s</error> left as it is:', $identifier));
                $io->listing($invalid->problems);
            } catch (SiteNotFound) {
                $failed = true;
                $io->writeln(sprintf(' <error>%s</error> is not in the registry.', $identifier));
            }
        }

        return $failed ? Command::FAILURE : Command::SUCCESS;
    }
}
