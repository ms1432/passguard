<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckPasswordRequest;
use App\Http\Requests\GeneratePasswordRequest;
use App\Password\PasswordGenerator;
use App\Password\PasswordStrengthChecker;
use Illuminate\Http\JsonResponse;

final class PasswordController extends Controller
{
    public function __construct(
        private readonly PasswordStrengthChecker $checker,
        private readonly PasswordGenerator $generator,
    ) {
    }

    public function check(CheckPasswordRequest $request): JsonResponse
    {
        return response()->json($this->checker->check($request->password())->toArray());
    }

    public function generate(GeneratePasswordRequest $request): JsonResponse
    {
        $length = $request->length();

        return response()->json([
            'password' => $this->generator->generate($length),
            'length' => $length,
        ]);
    }
}
