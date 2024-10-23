<?php

namespace Tests\Feature;

use App\Rules\RegistrationRule;
use App\Rules\Uppercase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\In;
use Illuminate\Validation\Rules\Password;
use Nette\Schema\ValidationException;
use Tests\TestCase;
use function PHPUnit\Framework\assertNotNull;
use function PHPUnit\Framework\assertTrue;

class ValidatorTest extends TestCase
{
    public function testValidator()
    {
        $data = [
            'username' => 'admin',
            'password' => '123456',
        ];

        $rules = [
            'username' => 'required',
            'password' => 'required',
        ];

        $validator = Validator::make($data, $rules);
        self::assertNotNull($validator);
        self::assertTrue($validator->passes());
        self::assertFalse($validator->fails());
    }

    public function testValidatorInvalid()
    {
        $data = [
            'username' => '',
            'password' => '',
        ];

        $rules = [
            'username' => 'required',
            'password' => 'required',
        ];

        $validator = Validator::make($data, $rules);
        self::assertNotNull($validator);
        self::assertFalse($validator->passes());
        self::assertTrue($validator->fails());

        $message = $validator->getMessageBag();
        Log::info($message->toJson(JSON_PRETTY_PRINT));
    }

    public function testValidatorValidationException()
    {
        $data = [
            'username' => '',
            'password' => '',
        ];

        $rules = [
            'username' => 'required',
            'password' => 'required',
        ];

        $validator = Validator::make($data, $rules);
        self::assertNotNull($validator);

        try {
            $validator->validate();
            self::fail('Validation Exception not thrown');
        } catch (ValidationException $exception) {
            assertNotNull($exception->validator);
            $message = $exception->validator->errors();
            Log::error($message->toJson(JSON_PRETTY_PRINT));
        }
    }

    public function testValidatorMultipleRules()
    {
        App::setLocale('id');
        $data = [
            'username' => 'admin',
            'password' => 'admin',
        ];

        $rules = [
            'username' => 'required|email|max:100',
            'password' => ['required', 'min:6', 'max:20'],
        ];

        $validator = Validator::make($data, $rules);
        self::assertNotNull($validator);
        self::assertFalse($validator->passes());
        self::assertTrue($validator->fails());

        $message = $validator->getMessageBag();
        Log::info($message->toJson(JSON_PRETTY_PRINT));
    }

    public function testValidatorValidData()
    {
        $data = [
            'username' => 'admin@mail.com',
            'password' => 'rahasia',
            'admin' => true,
            'other' => 'xxx'
        ];

        $rules = [
            'username' => 'required|email|max:100',
            'password' => ['required', 'min:6', 'max:20'],
        ];

        $validator = Validator::make($data, $rules);
        self::assertNotNull($validator);

        try {
            $valid = $validator->validate();
            Log::info(json_encode($valid, JSON_PRETTY_PRINT));
        } catch (ValidationException $exception) {
            assertNotNull($exception->validator);
            $message = $exception->validator->errors();
            Log::error($message->toJson(JSON_PRETTY_PRINT));
        }
    }

    public function testValidatorInlineMessage()
    {
        $data = [
            'username' => 'admin',
            'password' => 'admin',
        ];

        $rules = [
            'username' => 'required|email|max:100',
            'password' => ['required', 'min:6', 'max:20'],
        ];

        $messages = [
            'required' => ':attribute tidak boleh kosong',
            'email' => ':attribute tidak valid',
            'min' => ':attribute minimal :min karakter',
            'max' => ':attribute maksimal :max karakter',
        ];

        $validator = Validator::make($data, $rules, $messages);
        self::assertNotNull($validator);
        self::assertFalse($validator->passes());
        self::assertTrue($validator->fails());

        $message = $validator->getMessageBag();
        Log::info($message->toJson(JSON_PRETTY_PRINT));
    }

    public function testValidatorAdditionalValidation()
    {
        App::setLocale('id');
        $data = [
            'username' => 'admin@mail.com',
            'password' => 'admin@mail.com',
        ];

        $rules = [
            'username' => 'required|email|max:100',
            'password' => ['required', 'min:6', 'max:20'],
        ];

        $validator = Validator::make($data, $rules);
        $validator->after(function (\Illuminate\Validation\Validator $validator) {
            $data = $validator->getData();
            if ($data['username'] === $data['password']) {
                $validator->errors()->add('password', 'password tidak boleh sama dengan username');
            }
        });
        self::assertNotNull($validator);
        self::assertFalse($validator->passes());
        self::assertTrue($validator->fails());

        $message = $validator->getMessageBag();
        Log::info($message->toJson(JSON_PRETTY_PRINT));
    }

    public function testValidatorCustomRule()
    {
        App::setLocale('id');
        $data = [
            'username' => 'admin@mail.com',
            'password' => 'admin@mail.com',
        ];

        $rules = [
            'username' => ['required', 'email', 'max:100', new Uppercase()],
            'password' => ['required', 'min:6', 'max:20', new RegistrationRule()],
        ];

        $validator = Validator::make($data, $rules);
        self::assertNotNull($validator);
        self::assertFalse($validator->passes());
        self::assertTrue($validator->fails());

        $message = $validator->getMessageBag();
        Log::info($message->toJson(JSON_PRETTY_PRINT));
    }

    public function testValidatorCustomFunctionRule()
    {
        App::setLocale('id');
        $data = [
            'username' => 'admin@mail.com',
            'password' => 'admin@mail.com',
        ];

        $rules = [
            'username' => ['required', 'email', 'max:100', function (string $attribute, string $value, \Closure $fail) {
                if (strtoupper($value) !== $value) {
                    $fail(':attribute must be UPPERCASE');
                }
            }],
            'password' => ['required', 'min:6', 'max:20', new RegistrationRule()],
        ];

        $validator = Validator::make($data, $rules);
        self::assertNotNull($validator);
        self::assertFalse($validator->passes());
        self::assertTrue($validator->fails());

        $message = $validator->getMessageBag();
        Log::info($message->toJson(JSON_PRETTY_PRINT));
    }

    public function testValidatorRuleClasses()
    {
        App::setLocale('id');
        $data = [
            'username' => 'Ahzi',
            'password' => 'admin123@mail.com',
        ];

        $rules = [
            'username' => ['required', new In('Ahzi', 'Budi', 'Joko')],
            'password' => ['required', Password::min(6)->letters()->numbers()->symbols()],
        ];

        $validator = Validator::make($data, $rules);
        self::assertNotNull($validator);

        $message = $validator->getMessageBag();
        Log::info($message->toJson(JSON_PRETTY_PRINT));
    }

    public function testNestedArray()
    {
        $data = [
            'name' => [
                'first' => 'Ahmad',
                'last' => 'Fauzi',
            ],
            'address' => [
                'street' => 'Jl. Mangga',
                'city' => 'Jakarta',
                'country' => 'Indonesia',
            ]
        ];

        $rules = [
            'name.first' => ['required', 'max:100'],
            'name.last' => ['max:100'],
            'address.street' => ['max:100'],
            'address.city' => ['required', 'max:100'],
            'address.country' => ['required', 'max:100'],
        ];

        $validator = Validator::make($data, $rules);
        assertTrue($validator->passes());
    }

    public function testNestedIndexedArray()
    {
        $data = [
            'name' => [
                'first' => 'Ahmad',
                'last' => 'Fauzi',
            ],
            'address' => [
                [
                    'street' => 'Jl. Mangga',
                    'city' => 'Jakarta',
                    'country' => 'Indonesia',
                ],
                [
                    'street' => 'Jl. Mangga',
                    'city' => 'Jakarta',
                    'country' => 'Indonesia',
                ]
            ]
        ];

        $rules = [
            'name.first' => ['required', 'max:100'],
            'name.last' => ['max:100'],
            'address.*.street' => ['max:100'],
            'address.*.city' => ['required', 'max:100'],
            'address.*.country' => ['required', 'max:100'],
        ];

        $validator = Validator::make($data, $rules);
        assertTrue($validator->passes());
    }
}

