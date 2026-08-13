<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunityTest\Zed\SearchFeedbackGui\Communication\Form;

use Codeception\Test\Unit;
use SprykerCommunity\Zed\SearchFeedbackGui\Communication\Form\ReplyForm;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Validator\Validation;

/**
 * Builds the form through a plain Symfony `FormFactory` (real `symfony/form` + `symfony/validator`, no
 * Spryker DI involved) — this is a pure Symfony Form contract (does the `NotBlank` constraint actually
 * reject a blank reply), which does not need the real Zed container `DetailController`'s own
 * `getFactory()->createReplyForm()` resolves through.
 *
 * @group SprykerCommunityTest
 * @group Zed
 * @group SearchFeedbackGui
 * @group Communication
 * @group Form
 * @group ReplyFormTest
 * @group Portable
 */
class ReplyFormTest extends Unit
{
    public function testGetBlockPrefixReturnsTheDeclaredPrefix(): void
    {
        $this->assertSame('search_feedback_reply', (new ReplyForm())->getBlockPrefix());
    }

    public function testSubmittingABlankBodyIsInvalid(): void
    {
        $form = $this->createFormFactory()->create(ReplyForm::class);

        $form->submit([ReplyForm::FIELD_BODY => '']);

        $this->assertFalse($form->isValid());
    }

    public function testSubmittingANonBlankBodyIsValid(): void
    {
        $form = $this->createFormFactory()->create(ReplyForm::class);

        $form->submit([ReplyForm::FIELD_BODY => 'Thanks, looking into it.']);

        $this->assertTrue($form->isValid());
        $this->assertSame('Thanks, looking into it.', $form->getData()[ReplyForm::FIELD_BODY]);
    }

    protected function createFormFactory(): FormFactoryInterface
    {
        return Forms::createFormFactoryBuilder()
            ->addExtension(new ValidatorExtension(Validation::createValidator()))
            ->getFormFactory();
    }
}
