<?php

namespace App\Enums;

enum XConnectReasonCodeEnum: string
{
    case successful = '000';
    case invalid_query_format = '001';
    case query_service_invalid = '002';
    case service_not_allowed = '003';
    case country_code_invalid = '004';
    case wrong_number_length_or_format = '005';
    case range_not_allocated = '006';
    case number_not_found_onboard = '007';
    case number_not_found_remote = '008';
    case service_error = '009';
    case remote_timeout = '010';
    case customer_not_authorised = '012';
    case rate_limit_reached = '013';
    case subscriber_error = '100';
    case unknown_subscriber = '101';
    case absent_subscriber = '102';
    case telephone_service_not_provisioned = '103';
    case roaming_not_allowed = '104';
    case subscriber_busy = '105';
    case subscriber_blocked = '106';
    case equipment_error = '107';
    case phone_switched_off = '108';
    case destination_network_error = '109';
    case facility_not_supported = '110';
    case destination_network_busy = '111';
    case no_network_response = '112';
    case destination_error = '113';
    case destination_network_unavailable = '114';
    case service_not_supported = '115';
    case number_not_found_uk_port_history = '116';
    case internal_system_error = '120';
    case remote_error_gnr_returned = '141';
    case token_not_found = '142';
    case token_expired = '143';
    case token_does_not_match = '144';
    case name_does_not_match = '150';
    case name_does_not_exist = '151';
    case remote_query_failure_gnr_returned = '200';
    case absent_subscriber_live_status = '300';
    case unknown_subscriber_live_status = '301';
    case live_status_unavailable = '302';
    case subscriber_error_live_status = '303';
    case destination_network_error_live_status = '304';
    case call_barred = '305';
    case teleservice_not_provisioned = '306';
    case facility_not_supported_live_status = '307';
}
