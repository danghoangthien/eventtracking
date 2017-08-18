<?php
namespace Hyper\EventBundle\Form\Type;

use Symfony\Component\Form\AbstractType,
    Symfony\Component\Form\FormBuilderInterface,
    Symfony\Component\Form\FormEvents,
    Symfony\Component\Form\FormEvent;

class ApplicationTitleType extends AbstractType
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', 'text', array(
                'required' => false
            ))
            ->add('folder', 'text', array(
                'required' => false
            ))
            ->add('description', 'text', array(
                'required' => false
            ))
            ->add('appId', 'choice', array(
                'multiple' => true
            ))
            ->add('status', 'choice', array(
                'choices'   => array('0' => 'Disable', '1' => 'Enable'),
                'data' => 1,
                'required'  => false,
                'empty_value' => false,
            ));

            $formModifier = function (FormInterface $form) {
                if ($form->has('appId')) {
                    $form->remove('appId');
                    $form->add('position', EntityType::class, array(
                        'class'       => 'AppBundle:Position',
                        'placeholder' => '',
                        'choices'     => $positions,
                    ));
                }

            };

            $builder->get('appId')->resetViewTransformers();

            $builder->addEventListener(
                FormEvents::PRE_SET_DATA,
                function (FormEvent $event) {
                    $form = $event->getForm();
                    $data = $event->getData();
                    if ($form->has('appId')) {
                        $form->remove('appId');
                        $form->add('appId', 'choice', array(
                            'multiple' => true,
                            'choices' => $data['appId']
                        ));
                    }
                }
            );

            $builder->addEventListener(
                FormEvents::PRE_SUBMIT,
                function (FormEvent $event) {
                    $form = $event->getForm();
                    $data = $event->getData();
                    $listAppId = [];
                    if(!empty($data['appId'])) {
                        foreach ($data['appId'] as $key => $appId) {
                            $listAppId[$appId] = $appId;
                        }
                    }
                    if ($form->has('appId')) {
                        $form->remove('appId');
                        $form->add('appId', 'choice', array(
                            'multiple' => true,
                            'choices' => $listAppId
                        ));
                    }
                }
            );
    }

    /*
     * {@inheritdoc
     */
    public function getName()
    {
        return 'application_title';
    }
}