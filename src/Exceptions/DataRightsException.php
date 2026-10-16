<?php

namespace Vipertecpro\DataRightsDesk\Exceptions;

use RuntimeException;

/** Base class. Messages carry ids and keys, never personal data. */
class DataRightsException extends RuntimeException {}
