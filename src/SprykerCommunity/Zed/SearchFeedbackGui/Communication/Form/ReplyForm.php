<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchFeedbackGui\Communication\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class ReplyForm extends AbstractType
{
    /**
     * @var string
     */
    public const FIELD_BODY = 'body';

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder->add(static::FIELD_BODY, TextareaType::class, [
            'label' => 'Reply',
            'constraints' => [
                new NotBlank(),
            ],
        ]);
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'search_feedback_reply';
    }
}
