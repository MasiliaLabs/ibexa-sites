<?php

declare(strict_types=1);

namespace Masilia\IbexaSites;

use Doctrine\ORM\EntityManagerInterface;
use Masilia\Contracts\IbexaSites\SiteInvalid;
use Masilia\Contracts\IbexaSites\SiteSettings;
use Masilia\IbexaSites\Persistence\Entity\SettingRecord;
use Masilia\IbexaSites\Persistence\SettingReader;
use Masilia\IbexaSites\Persistence\SiteReader;
use Masilia\IbexaSites\Runtime\DefaultSiteaccess;
use Masilia\IbexaSites\Validation\SiteFacts;

/** The platform settings in the database: validated against what exists, and the run time told to forget what it had read. */
readonly class DoctrineSiteSettings implements SiteSettings
{
    public function __construct(
        private EntityManagerInterface $entities,
        private SettingReader $settings,
        private SiteReader $sites,
        private SiteFacts $facts,
        private DefaultSiteaccess $default,
    ) {
    }

    public function defaultSiteaccess(): ?string
    {
        return $this->settings->value(DefaultSiteaccess::SETTING);
    }

    public function setDefaultSiteaccess(?string $siteaccess): void
    {
        if ($siteaccess !== null && !in_array($siteaccess, $this->defaultSiteaccessChoices(), true)) {
            throw new SiteInvalid([sprintf('"%s" is not a siteaccess that can answer by default. Available: %s.', $siteaccess, implode(', ', $this->defaultSiteaccessChoices()))]);
        }
        $record = $this->entities->getRepository(SettingRecord::class)->find(DefaultSiteaccess::SETTING);
        if ($siteaccess === null) {
            if ($record !== null) {
                $this->entities->remove($record);
            }
        } elseif ($record === null) {
            $this->entities->persist(new SettingRecord(DefaultSiteaccess::SETTING, $siteaccess));
        } else {
            $record->change($siteaccess);
        }
        $this->entities->flush();
        $this->default->invalidate();
    }

    public function defaultSiteaccessChoices(): array
    {
        $names = $this->facts->staticFrontSiteaccesses();
        foreach ($this->sites->all() as $site) {
            if ($site->status->isServed()) {
                $names = [...$names, ...$site->siteaccessNames()];
            }
        }

        return array_values(array_unique($names));
    }
}
