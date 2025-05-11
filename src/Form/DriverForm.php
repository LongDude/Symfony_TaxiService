<?php

namespace App\Form;

use App\Entity\Driver;
use App\Entity\Tariff;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class DriverForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder
            ->add('email', EmailType::class, [
                'mapped' => false,
                'disabled' => !empty($options['user_email']),
                'data' => $options['user_email'],
                'attr' => ['class' => 'inp_email'],
            ])
            ->add('name', TextType::class, [
                'mapped' => false,
                'data' => $options['user_name'],
                'attr' => ['class' => 'inp_name'],
            ])
            ->add('phone', TextType::class, [
                'mapped' => false,
                'data' => $options['user_phone'],
                'attr' => ['class' => 'inp_phone'],
            ])
            ->add('intership', IntegerType::class, [
                'attr' => ['class' => 'inp_intership'],
            ])
            ->add('car_license', TextType::class, [
                'attr' => ['class' => 'inp_car_license'],
            ])
            ->add('car_brand', TextType::class, [
                'attr' => ['class' => 'inp_car_brand'],
            ] )
            ->add('tariff', EntityType::class, [
                'class' => Tariff::class,
                'required' => false,
                'choice_label' => 'name',
            ])
        ;
        if (empty($options['user_email'])){
            $builder
            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a password',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Your password should be at least {{ limit }} characters',
                        'max' => 4096,
                    ]),
                ],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Driver::class,
            'user_name' => null,
            'user_email' => null,
            'user_phone' => null,
        ]);
    }
}
