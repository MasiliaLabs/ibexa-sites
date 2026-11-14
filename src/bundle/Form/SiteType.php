<?php

declare(strict_types=1);

namespace Masilia\Bundle\IbexaSites\Form;

use Masilia\Contracts\IbexaSites\LanguageMode;
use Masilia\IbexaSites\Validation\SiteFacts;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** The form of a site: its name, where it answers, in which languages, from which root, with which design. */
final class SiteType extends AbstractType
{
    public function __construct(private readonly SiteFacts $facts)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $languages = array_combine($this->facts->languages(), $this->facts->languages());
        $designs = array_combine($this->facts->designs(), $this->facts->designs());
        $bases = array_combine($this->facts->baseGroups(), $this->facts->baseGroups());
        $admins = array_combine($this->facts->adminSiteaccesses(), $this->facts->adminSiteaccesses());

        $builder
            ->add('identifier', TextType::class, ['label' => 'form.identifier', 'help' => 'form.identifier.help', 'disabled' => $options['editing']])
            ->add('name', TextType::class, ['label' => 'form.name'])
            ->add('hosts', TextareaType::class, ['label' => 'form.hosts', 'help' => 'form.hosts.help', 'attr' => ['rows' => 3]])
            ->add('languages', ChoiceType::class, ['label' => 'form.languages', 'help' => 'form.languages.help', 'choices' => $languages, 'multiple' => true, 'expanded' => true])
            ->add('defaultLanguage', ChoiceType::class, ['label' => 'form.default_language', 'choices' => $languages, 'placeholder' => false])
            ->add('languageMode', ChoiceType::class, ['label' => 'form.language_mode', 'help' => 'form.language_mode.help', 'choices' => ['form.language_mode.prefix' => LanguageMode::Prefix->value, 'form.language_mode.host' => LanguageMode::Host->value], 'placeholder' => false])
            ->add('rootLocationId', IntegerType::class, ['label' => 'form.root', 'help' => 'form.root.help'])
            ->add('design', ChoiceType::class, ['label' => 'form.design', 'help' => 'form.design.help', 'choices' => $designs, 'placeholder' => false])
            ->add('base', ChoiceType::class, ['label' => 'form.base', 'help' => 'form.base.help', 'choices' => $bases, 'placeholder' => false])
            ->add('adminSiteaccess', ChoiceType::class, ['label' => 'form.admin', 'help' => 'form.admin.help', 'choices' => $admins, 'required' => false, 'placeholder' => 'form.admin.none']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => SiteData::class, 'editing' => false, 'translation_domain' => 'masilia_ibexa_sites']);
        $resolver->setAllowedTypes('editing', 'bool');
    }
}
