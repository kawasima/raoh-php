<?php

declare(strict_types=1);

namespace Raoh;

enum ErrorCodes: string
{
    case Required         = 'required';
    case Blank            = 'blank';
    case TooShort         = 'too_short';
    case TooLong          = 'too_long';
    case InvalidLength    = 'invalid_length';
    case OutOfRange       = 'out_of_range';
    case NotMultipleOf    = 'not_multiple_of';
    case InvalidScale     = 'invalid_scale';
    case TooSmall         = 'too_small';
    case TooBig           = 'too_big';
    case InvalidSize      = 'invalid_size';
    case InvalidValue     = 'invalid_value';
    case InvalidFormat    = 'invalid_format';
    case TypeMismatch     = 'type_mismatch';
    case UnknownField     = 'unknown_field';
    case MissingElement   = 'missing_element';
    case MissingElements  = 'missing_elements';
    case DuplicateElement = 'duplicate_element';
    case NotAllowed       = 'not_allowed';
    case OneOfFailed      = 'one_of_failed';
}
