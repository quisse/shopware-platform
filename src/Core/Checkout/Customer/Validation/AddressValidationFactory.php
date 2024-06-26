<?php declare(strict_types=1);

namespace Shopware\Core\Checkout\Customer\Validation;

use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Validation\EntityExists;
use Shopware\Core\Framework\Feature;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Validation\DataValidationDefinition;
use Shopware\Core\Framework\Validation\DataValidationFactoryInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

#[Package('checkout')]
class AddressValidationFactory implements DataValidationFactoryInterface
{
    /**
     * @internal
     */
    public function __construct(private readonly SystemConfigService $systemConfigService)
    {
    }

    public function create(SalesChannelContext $context): DataValidationDefinition
    {
        $definition = new DataValidationDefinition('address.create');

        $this->buildCommonValidation($definition, $context);

        return $definition;
    }

    public function update(SalesChannelContext $context): DataValidationDefinition
    {
        $definition = new DataValidationDefinition('address.update');

        $this->buildCommonValidation($definition, $context)
            ->add('id', new NotBlank(), new EntityExists(['context' => $context->getContext(), 'entity' => 'customer_address']));

        return $definition;
    }

    private function buildCommonValidation(DataValidationDefinition $definition, SalesChannelContext $context): DataValidationDefinition
    {
        $frameworkContext = $context->getContext();
        $salesChannelId = $context->getSalesChannel()->getId();

        $definition
            ->add('salutationId', new EntityExists(['entity' => 'salutation', 'context' => $frameworkContext]))
            ->add('countryId', new EntityExists(['entity' => 'country', 'context' => $frameworkContext]))
            ->add('countryStateId', new EntityExists(['entity' => 'country_state', 'context' => $frameworkContext]))
            ->add('firstName', new NotBlank())
            ->add('lastName', new NotBlank())
            ->add('street', new NotBlank())
            ->add('city', new NotBlank())
            ->add('countryId', new NotBlank(), new EntityExists(['entity' => 'country', 'context' => $frameworkContext]));

        if ($this->systemConfigService->get('core.loginRegistration.showAdditionalAddressField1', $salesChannelId)
            && $this->systemConfigService->get('core.loginRegistration.additionalAddressField1Required', $salesChannelId)) {
            $definition->add('additionalAddressLine1', new NotBlank());
        }

        if ($this->systemConfigService->get('core.loginRegistration.showAdditionalAddressField2', $salesChannelId)
            && $this->systemConfigService->get('core.loginRegistration.additionalAddressField2Required', $salesChannelId)) {
            $definition->add('additionalAddressLine2', new NotBlank());
        }

        if ($this->systemConfigService->get('core.loginRegistration.showPhoneNumberField', $salesChannelId)
            && $this->systemConfigService->get('core.loginRegistration.phoneNumberFieldRequired', $salesChannelId)) {
            $definition->add('phoneNumber', new NotBlank());
        }

        if ($this->systemConfigService->get('core.loginRegistration.showPhoneNumberField', $salesChannelId)) {
            $definition->add('phoneNumber', new Length(['max' => CustomerAddressDefinition::MAX_LENGTH_PHONE_NUMBER]));
        }

        /**
         * @deprecated tag:v6.7.0 - fields "firstName", "lastName", "title", "zipcode" will have a maximum length.
         */
        if (Feature::isActive('v6.7.0.0')) {
            $definition
                ->add('firstName', new Length(['max' => CustomerAddressDefinition::MAX_LENGTH_FIRST_NAME]))
                ->add('lastName', new Length(['max' => CustomerAddressDefinition::MAX_LENGTH_LAST_NAME]))
                ->add('title', new Length(['max' => CustomerAddressDefinition::MAX_LENGTH_TITLE]))
                ->add('zipcode', new Length(['max' => CustomerAddressDefinition::MAX_LENGTH_ZIPCODE]));
        }

        return $definition;
    }
}
