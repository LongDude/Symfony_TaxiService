<?php

namespace App\Form;

use App\Entity\Tariff;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TariffForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'attr' => ['class' => 'inp_name'],
                'label' => 'Название тарифа',
            ])
            ->add('base_price', NumberType::class, [
                'attr' => ['class' => 'inp_base_price'],
                'label' => 'Стоимость тарифа',
            ])
            ->add('base_dist', NumberType::class, [
                'attr' => ['class' => 'inp_base_dist'],
                'label' => 'Расстояние, включенное в тариф',
            ])
            ->add('dist_cost', NumberType::class, [
                'attr' => ['class' => 'inp_dist_cost'],
                'label' => 'Цена за километр',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Tariff::class,
        ]);
    }
}
