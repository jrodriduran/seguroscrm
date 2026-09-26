<?php

namespace Webkul\Lead\Providers;

use Webkul\Core\Providers\BaseModuleServiceProvider;
use Webkul\Lead\Models\AssignmentRule;
use Webkul\Lead\Models\HouseholdMember;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Models\LeadConsent;
use Webkul\Lead\Models\LeadConsentVersion;
use Webkul\Lead\Models\LeadCrossSellOpportunity;
use Webkul\Lead\Models\LeadDmiDocument;
use Webkul\Lead\Models\LeadDoctorNetwork;
use Webkul\Lead\Models\LeadRxMedication;
use Webkul\Lead\Models\LeadTaxHousehold;
use Webkul\Lead\Models\Pipeline;
use Webkul\Lead\Models\PolicyCoverageStatusHistory;
use Webkul\Lead\Models\Product;
use Webkul\Lead\Models\SlaRule;
use Webkul\Lead\Models\Source;
use Webkul\Lead\Models\Stage;
use Webkul\Lead\Models\Type;
use Webkul\Lead\Models\UserAgentLicense;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    protected $models = [
        AssignmentRule::class,
        HouseholdMember::class,
        Lead::class,
        LeadConsent::class,
        LeadConsentVersion::class,
        LeadCrossSellOpportunity::class,
        LeadDmiDocument::class,
        LeadDoctorNetwork::class,
        LeadRxMedication::class,
        LeadTaxHousehold::class,
        Pipeline::class,
        PolicyCoverageStatusHistory::class,
        Product::class,
        SlaRule::class,
        Source::class,
        Stage::class,
        Type::class,
        UserAgentLicense::class,
    ];
}
