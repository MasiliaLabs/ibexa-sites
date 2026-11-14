<?php

declare(strict_types=1);

namespace Masilia\Bundle\IbexaSites\Form;

use Masilia\IbexaSites\Validation\SiteFacts;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** One siteaccess of a site: its name, its language (and the ones it falls back to), the host and the prefix it answers on. */
final class SiteAccessType extends AbstractType
{
    public function __construct(private readonly SiteFacts $facts)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $languages = array_combine($this->facts->languages(), $this->facts->languages());

        $builder
            ->add('name', TextType::class, ['label' => 'form.siteaccess.name', 'help' => 'form.siteaccess.name.help'])
            ->add('language', ChoiceType::class, ['label' => 'form.siteaccess.language', 'choices' => $languages, 'placeholder' => false])
            ->add('fallbacks', ChoiceType::class, ['label' => 'form.siteaccess.fallbacks', 'help' => 'form.siteaccess.fallbacks.help', 'choices' => $languages, 'multiple' => true, 'expanded' => true, 'required' => false])
            ->add('host', TextType::class, ['label' => 'form.siteaccess.host', 'help' => 'form.siteaccess.host.help'])
            ->add('uriPrefix', TextType::class, ['label' => 'form.siteaccess.prefix', 'help' => 'form.siteaccess.prefix.help', 'required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => SiteAccessData::class, 'translation_domain' => 'masilia_ibexa_sites']);
    }
}
