<?php

namespace App\Form;

use App\Dto\VeterinarioDTO;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class VeterinarioType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nome', TextType::class, [
                'label' => 'Nome do Veterinário',
                'invalid_message' => 'Informe um nome válido para o veterinário.',

                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex.: Dr. Carlos Silva',
                ],

                'constraints' => [
                    new NotBlank(
                        message: 'O nome do veterinário é obrigatório.',
                    ),
                    new Length(
                        max: 150,
                        maxMessage: 'O nome do veterinário não pode exceder {{ limit }} caracteres.',
                    ),
                ],
            ])

            ->add('crmv', TextType::class, [
                'label' => 'CRMV',
                'invalid_message' => 'Informe um CRMV válido.',

                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex.: CRMV-SP 12345',
                ],

                'constraints' => [
                    new NotBlank(
                        message: 'O CRMV é obrigatório.',
                    ),
                    new Regex(
                        pattern: '/^CRMV-(SP|RJ|MG)\s\d{4,6}$/',
                        message: 'O CRMV deve seguir o formato CRMV-XX 12345',
                    )
                ],
            ])

            ->add('fazendasIds', ChoiceType::class, [
                'choices' => $this->normalizarChoices($options['fazendas_choices']),
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'invalid_message' => 'Selecione apenas fazendas válidas.',
                'label' => 'Fazendas',
            ]) 
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => VeterinarioDTO::class,
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
