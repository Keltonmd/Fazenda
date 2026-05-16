<?php

namespace App\Form;

use App\Dto\FazendaDTO;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

class FazendaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nome', TextType::class, [
                'label' => 'Nome da Fazenda',
                'invalid_message' => 'Informe um nome válido para a fazenda.',

                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex.: Fazenda Boa Vista',
                ],

                'constraints' => [
                    new NotBlank(
                        message: 'O nome da fazenda é obrigatório.',
                    ),
                    
                    new Length(
                        max: 150,
                        maxMessage: 'O nome da fazenda não pode exceder {{ limit }} caracteres.',
                        min: 3,
                        minMessage: 'O nome da fazenda deve conter pelo menos {{ limit }} caracteres.',
                    ),
                ],
            ])

            ->add('responsavel', TextType::class, [
                'label' => 'Responsável pela Fazenda',
                'invalid_message' => 'Informe um responsável válido.',

                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex.: João Silva',
                ],

                'constraints' => [
                    new NotBlank(
                        message: 'O nome do responsável é obrigatório.',
                    ),

                    new Length(
                        max: 100,
                        maxMessage: 'O nome do responsável não pode exceder {{ limit }} caracteres.',
                        min: 3,
                        minMessage: 'O nome do responsável deve conter pelo menos {{ limit }} caracteres.',
                    ),
                ],
            ])

            ->add('tamanhoHA', NumberType::class, [
                'label' => 'Área da Fazenda (em hectares)',
                'invalid_message' => 'Informe uma área válida para a fazenda.',

                'scale' => 2,
                
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex.: 100',
                    'step' => '0.01',
                    'min' => 0,
                ],

                'constraints' => [
                    new PositiveOrZero(
                        message: 'A área da fazenda deve ser um número positivo ou zero.',
                    )
                ],
            ])

            ->add('veterinariosIds', ChoiceType::class, [
                'choices' => $this->normalizarChoices($options['veterinarios_choices']),
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'invalid_message' => 'Selecione apenas veterinários válidos.',
                'label' => 'Veterinários',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FazendaDTO::class,
            'veterinarios_choices' => [],
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
