<?php
namespace Bravo3\Orm\Enum;

use Eloquent\Enumeration\AbstractEnumeration;

final class Event extends AbstractEnumeration
{
    const PRE_PERSIST         = 'persist.pre';
    const POST_PERSIST        = 'persist.post';
    const PRE_FLUSH           = 'flush.pre';
    const POST_FLUSH          = 'flush.post';
    const PRE_RETRIEVE        = 'retrieve.pre';
    const POST_RETRIEVE       = 'retrieve.post';
    const PRE_DELETE          = 'delete.pre';
    const POST_DELETE         = 'delete.post';
    const HYDRATION_EXCEPTION = 'hydration_exception';
}
