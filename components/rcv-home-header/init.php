<?php
$rcv_opportunityId = $app->config['rcv.opportunityId'];

$opportunity = $app->repo('Opportunity')->find($rcv_opportunityId);

$this->jsObject['config']['rcvHomeHeader']['rcvOpportunityId'] = $opportunity->id;
