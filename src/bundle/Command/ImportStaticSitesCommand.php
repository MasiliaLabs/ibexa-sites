<?php

declare(strict_types=1);

namespace Masilia\Bundle\IbexaSites\Command;

use Masilia\Contracts\IbexaSites\SiteInvalid;
use Masilia\Contracts\IbexaSites\SiteRegistry;
use Masilia\IbexaSites\Ibexa\StaticSites;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Takes the sites of the project's YAML into the registry, as drafts: nothing changes for the visitors until the YAML lets go of a
 * site and the draft is activated. The hosts are those of the environment the command runs in.
 */
#[AsCommand(name: 'masilia:sites:import-static', description: 'Describe the sites declared in the YAML in the registry, as drafts')]
final class ImportStaticSitesCommand extends Command
{
    public function __construct(private readonly StaticSites $static, private readonly SiteRegistry $registry)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be imported without writing it');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dry = (bool) $input->getOption('dry-run');
        $skipped = [];
        $failed = false;
        foreach ($this->static->all($skipped) as $site) {
            $io->section(sprintf('%s (root %d, design %s%s)', $site->identifier, $site->rootLocationId, $site->design, $site->sessionCookieDomain === null ? '' : ', cookie ' . $site->sessionCookieDomain));
            $io->listing(array_map(static fn ($s): string => sprintf('%s  %s  %s%s', $s->name, implode(' → ', $s->languages), $s->host, $s->uriPrefix === null ? '' : '/' . $s->uriPrefix), $site->siteaccesses));
            if ($this->registry->find($site->identifier) !== null) {
                $io->note('Already in the registry: left as it is.');
                continue;
            }
            if ($dry) {
                continue;
            }
            try {
                $this->registry->create($site);
                $io->success('Imported as a draft.');
            } catch (SiteInvalid $invalid) {
                $failed = true;
                $io->error($invalid->problems);
            }
        }
        if ($skipped !== []) {
            $io->warning($skipped);
        }

        return $failed ? Command::FAILURE : Command::SUCCESS;
    }
}
