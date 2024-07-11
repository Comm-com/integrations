<?php

namespace App\Services;

use App\Models\MobileNetwork;
use Illuminate\Support\Facades\Log;

class NetworkService
{
    public function getNetworkByMccMnc($mcc, $mnc): ?MobileNetwork
    {
        return MobileNetwork::whereMcc($mcc)->whereMnc($mnc)->first();
    }

    public function guessNetworkId($mcc = null, $mnc = null, $hni = null): ?int
    {
        if ( ! empty($hni)) {
            [$mcc, $mnc] = $this->hni2MccMnc($hni);
        }

        if (empty($mcc) || empty($mnc)) {
            return null;
        }

        $network = $this->getNetworkByMccMnc($mcc, $mnc);

        return $network?->id;
    }

    public function createNewNetwork($mcc = null, $mnc = null, $hni = null): ?int
    {
        if ( ! empty($hni)) {
            [$mcc, $mnc] = $this->hni2MccMnc($hni);
        }

        if (empty($mcc) || empty($mnc)) {
            return null;
        }

        Log::debug('Creating new network', [
            'mcc' => $mcc,
            'mnc' => $mnc,
        ]);

        $network = $this->getNetworkByMccMnc($mcc, $mnc);

        if ( ! empty($network)) {
            return $network->id;
        }

        // todo: need to get country and other info
        return null;

        $network = MobileNetwork::create([
            'mcc' => $mcc,
            'mnc' => $mnc,
            'brand' => $mcc . '-' . $mnc, // todo: get name?
        ]);

        Log::info('Created new network', [
            'network' => $network,
        ]);

        return $network->id;
    }

    private function hni2MccMnc($hni): array
    {
        $mcc = mb_substr($hni, 0, 3);
        $mnc = mb_substr($hni, 3);

        return [$mcc, $mnc];
    }
}
