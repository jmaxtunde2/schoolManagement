<?php

namespace Tests\Feature\Communication;

use App\Services\Sms\MetaWhatsAppProvider;
use App\Support\BeninPhoneNumber;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BeninPhoneNumberTest extends TestCase
{
    public function test_it_formats_benin_numbers_for_sms_with_the_01_prefix_and_without_plus(): void
    {
        $inputs = [
            '+229 01 69 42 29 76',
            '+22969422976',
            '0022969422976',
            '2290169422976',
            '22969422976',
            '0169422976',
        ];

        foreach ($inputs as $input) {
            $this->assertSame('2290169422976', BeninPhoneNumber::forSms($input), $input);
        }
    }

    public function test_it_formats_benin_numbers_for_whatsapp_without_the_01_prefix_and_with_plus(): void
    {
        $inputs = [
            '+229 01 69 42 29 76',
            '+22969422976',
            '0022969422976',
            '2290169422976',
            '22969422976',
            '0169422976',
        ];

        foreach ($inputs as $input) {
            $this->assertSame('+22969422976', BeninPhoneNumber::forWhatsApp($input), $input);
        }
    }

    public function test_it_strips_the_01_prefix_from_an_already_formatted_whatsapp_number(): void
    {
        $this->assertSame('+22969422976', BeninPhoneNumber::withoutNationalPrefix('+2290169422976'));
    }

    public function test_it_leaves_non_benin_numbers_untouched(): void
    {
        $this->assertNull(BeninPhoneNumber::forSms('+22890000001'));
        $this->assertNull(BeninPhoneNumber::forWhatsApp('+22890000001'));
    }

    public function test_it_can_assume_a_bare_eight_digit_number_is_benin(): void
    {
        $this->assertNull(BeninPhoneNumber::forSms('90000001'));
        $this->assertSame('2290190000001', BeninPhoneNumber::forSms('90000001', true));
        $this->assertSame('+22990000001', BeninPhoneNumber::forWhatsApp('90000001', true));
    }

    public function test_meta_whatsapp_strips_the_benin_01_prefix(): void
    {
        config([
            'services.whatsapp.token' => 'fake-whatsapp-token',
            'services.whatsapp.phone_number_id' => '106732507566763',
        ]);

        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'messages' => [['id' => 'wamid.ok']],
            ], 200),
        ]);

        $response = (new MetaWhatsAppProvider)->send('+229 01 69 42 29 76', 'Bonjour');

        $this->assertTrue($response->success);
        Http::assertSent(fn (HttpRequest $request) => $request['to'] === '+22969422976');
    }
}
