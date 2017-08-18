<?php
namespace Hyper\EventBundle\Form\Type;

use Symfony\Component\Form\AbstractType,
    Symfony\Component\Form\FormBuilderInterface,
    Symfony\Component\Form\FormEvents,
    Symfony\Component\Form\FormEvent,
    Doctrine\ORM\EntityRepository,
    Hyper\Domain\Client\Client;

class ClientType extends AbstractType
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', 'text', array(
                'required' => false
            ))
            ->add('appTitle', 'choice', array(
                'multiple' => true
            ))
            ->add('appTitle', 'entity', array(
                'multiple' => true,
                'class' => 'Hyper\Domain\Application\ApplicationTitle',
                'choice_label' => 'title',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('a')->where('a.status = 1');
                },
            ))
            ->add('accountType', 'choice', array(
                'choices' => array(
                    1 => 'E-commerce',
                    2 => 'Gaming',
                    3 => 'Branding'
                ),
                'required' => true
            ))
            ->add('usagePlanType', 'choice', array(
                'choices' => Client::USAGE_PLAN_TYPE,
                'expanded' => true,
                'required' => true,
                'empty_data' => Client::USAGE_PLAN_TYPE_FREE_PLAN
            ))
            ->add('userLimit', 'text', array(
                'required' => false
            ));
    }

    /*
     * {@inheritdoc
     */
    public function getName()
    {
        return 'client';
    }
}