<?php

declare(strict_types=1);

namespace Masilia\Bundle\IbexaSites\Form;

use Masilia\IbexaSites\Validation\SiteFacts;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** The form of a site: what the group shares (root, design, base) and its siteaccesses (language, host, prefix). */
final class SiteType extends AbstractType
{
    public function __construct(private readonly SiteFacts $facts)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $designs = array_combine($this->facts->designs(), $this->facts->designs());
        $bases = array_combine($this->facts->baseGroups(), $this->facts->baseGroups());
        $admins = array_combine($this->facts->adminSiteaccesses(), $this->facts->adminSiteaccesses());

        $builder
            ->add('identifier', TextType::class, ['label' => 'form.identifier', 'help' => 'form.identifier.help', 'disabled' => $options['editing']])
            ->add('name', TextType::class, ['label' => 'form.name'])
            ->add('rootLocationId', IntegerType::class, ['label' => 'form.root', 'help' => 'form.root.help'])
            ->add('design', ChoiceType::class, ['label' => 'form.design', 'help' => 'form.design.help', 'choices' => $designs, 'placeholder' => false])
            ->add('base', ChoiceType::class, ['label' => 'form.base', 'help' => 'form.base.help', 'choices' => $bases, 'required' => false, 'placeholder' => 'form.base.none'])
            ->add('varDir', TextType::class, ['label' => 'form.var_dir', 'help' => 'form.var_dir.help', 'required' => false])
            ->add('layoutRemoteId', TextType::class, ['label' => 'form.layout', 'help' => 'form.layout.help', 'required' => false])
            ->add('sessionCookieDomain', TextType::class, ['label' => 'form.cookie_domain', 'help' => 'form.cookie_domain.help', 'required' => false])
            ->add('adminSiteaccess', ChoiceType::class, ['label' => 'form.admin', 'help' => 'form.admin.help', 'choices' => $admins, 'required' => false, 'placeholder' => 'form.admin.none'])
            ->add('siteaccesses', CollectionType::class, ['label' => 'form.siteaccesses', 'entry_type' => SiteAccessType::class, 'allow_add' => true, 'allow_delete' => true, 'by_reference' => false, 'prototype' => true, 'entry_options' => ['label' => false]]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => SiteData::class, 'editing' => false, 'translation_domain' => 'masilia_ibexa_sites']);
        $resolver->setAllowedTypes('editing', 'bool');
    }
}
