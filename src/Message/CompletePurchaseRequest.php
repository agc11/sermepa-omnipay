<?php

namespace Omnipay\Sermepa\Message;

use Symfony\Component\HttpFoundation\Request;
use Omnipay\Sermepa\Encryptor\Encryptor;
use Omnipay\Sermepa\Exception\BadSignatureException;
use Omnipay\Sermepa\Exception\CallbackException;

/**
 * Sermepa (Redsys) Complete Purchase Request
 */
class CompletePurchaseRequest extends PurchaseRequest
{
    public function getData()
    {
        $request = Request::createFromGlobals();

        $rawParameters = $request->get('Ds_MerchantParameters');

        $decodedParameters = json_decode(base64_decode(strtr($rawParameters, '-_', '+/')), true);

        if (!$this->checkSignature(
            $rawParameters,
            $decodedParameters['Ds_Order'],
            $request->get('Ds_Signature')
        )
        ) {
            throw new BadSignatureException();
        }

        //check response, code "000" to "099" means success
        if ((int)$decodedParameters['Ds_Response'] <= 99) {
            $success = true;
        } else {
            $success = false;
        }

        return [
            'success' => $success,
            'decodedParameters' => $decodedParameters
        ];
    }

    public function sendData($data)
    {
        return $this->response = new CompletePurchaseResponse($this, $data);
    }

    private function checkSignature($data, $orderId, $expectedSignature)
    {
        $key = Encryptor::encrypt_3DES($orderId, base64_decode($this->getParameter('merchantKey')));

        return strtr(base64_encode(hash_hmac('sha256', $data, $key, true)), '+/', '-_') == $expectedSignature;
    }
}
