<?php

namespace App\Form;

use App\Dto\GadoDTO;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

class GadoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('codigo', IntegerType::class, [
                'label' => 'Código do Gado',
                'invalid_message' => 'Informe um código válido para o gado.',

                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex.: 1',
                    'min' => 1,
                ],

                'constraints' => [
                    new NotBlank(
                        message: 'Informe o código do gado.'
                    ),

                    new GreaterThanOrEqual(
                        value: 1,
                        message: 'O código deve ser maior que 0.'
                    )
                ], 
            ])

            ->add('leite', NumberType::class, [
                'label' => 'Leite (L/semana)',
                'invalid_message' => 'Informe uma produção de leite válida.',

                'scale' => 1,

                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex.: 20.5',
                    'step' => 0.1,
                    'min' => 0,
                ],

                'constraints' => [
                    new NotBlank(
                        message: 'Informe a produção de leite do gado.'
                    ),

                    new PositiveOrZero(
                        message: 'A produção de leite não pode ser negativa.'
                    )
                ], 
            ])

            ->add('racao', NumberType::class, [
                'label' => 'Ração (kg/semana)',
                'invalid_message' => 'Informe uma quantidade de ração válida.',

                'scale' => 1,

                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex.: 15.0',
                    'step' => 0.1,
                    'min' => 0,
                ],

                'constraints' => [
                    new NotBlank(
                        message: 'Informe a quantidade de ração do gado.'
                    ),

                    new PositiveOrZero(
                        message: 'A quantidade de ração não pode ser negativa.'
                    )
                ], 
            ])

            ->add('peso', NumberType::class, [
                'label' => 'Peso (kg)',
                'invalid_message' => 'Informe um peso válido.',

                'scale' => 1,

                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex.: 500.00',
                    'step' => 0.01,
                    'min' => 0,
                ],

                'constraints' => [
                    new NotBlank(
                        message: 'Informe o peso do gado.'
                    ),

                    new PositiveOrZero(
                        message: 'O peso não pode ser negativo.'
                    )
                ], 
            ])

            ->add('nascimento', DateType::class, [
                'label' => 'Data de Nascimento',
                'invalid_message' => 'Informe uma data de nascimento válida.',

                'input' => 'datetime_immutable',
                'widget' => 'single_text',

                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex.: 2020-01-01',
                ],

                'constraints' => [
                    new NotBlank(
                        message: 'Informe a data de nascimento do gado.'
                    ),
                ],
            ])

            ->add('fazendaId', ChoiceType::class, [
                'choices' => $this->normalizarChoices($options['fazendas_choices']),
                'placeholder' => 'Selecione a fazenda',
                'label' => 'Fazenda',
                'invalid_message' => 'Selecione uma fazenda válida.',

                'attr' => [
                    'class' => 'form-select',
                ],

                'constraints' => [
                    new NotBlank(
                        message: 'Selecione a fazenda do gado.'
                    ),
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => GadoDTO::class,
            'fazendas_choices' => [],
        ]);
    }

    private function normalizarChoices(array $choices): array
    {
        $normalizados = [];

        foreach ($choices as $choice) {
            $nome = method_exists($choice, 'getNome') ? $choice->getNome() : null;
            $id = method_exists($choice, 'getId') ? $choice->getId() : null;

            if ($nome !== null && $id !== null) {
                $normalizados[$nome] = $id;
            }
        }

        return $normalizados;
    }
}
