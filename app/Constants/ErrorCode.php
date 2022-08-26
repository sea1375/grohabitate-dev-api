<?php

namespace App\Constants;

class ErrorCode {
    const INVALID_CONFIRMATION_LINK = 1;
    const INVALID_LOGIN = 2;
    const WRONG_PASSWORD = 3;
    const CONFIRM_PASSWORD_DISMATCHED = 4;
    const GOOGLE_OTP_INVALID = 5;
    const INVALID_REQUEST = 6;
    const G2FKEY_ALREADY_CREATED = 7;
    const CONFIRMATION_LINK_EXPIRED = 8;
}