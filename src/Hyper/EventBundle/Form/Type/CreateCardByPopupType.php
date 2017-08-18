<?php
namespace Hyper\EventBundle\Form\Type;

use Symfony\Component\Form\AbstractType,
    Symfony\Component\Form\FormBuilderInterface,
    Symfony\Component\Form\FormEvents,
    Symfony\Component\Form\FormEvent,
    Symfony\Component\OptionsResolver\OptionsResolver,
    Doctrine\ORM\EntityRepository;

class CreateCardByPopupType extends AbstractType
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', 'text')
            ->add('desc', 'text')
            ->add('platform_ios', 'checkbox')
            ->add('platform_android', 'checkbox')
            ->add('target_ghost', 'checkbox')
            ->add('target_dormant','checkbox')
            ->add('app_title_id','hidden');
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
        ]);
    }

    /*
     * {@inheritdoc
     */
    public function getName()
    {
        return 'create_card_by_popup';
    }
}