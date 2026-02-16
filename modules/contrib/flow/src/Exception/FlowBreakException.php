<?php

namespace Drupal\flow\Exception;

/**
 * Thrown when a task tells the Flow engine to stop the current flow.
 *
 * When this exception is thrown, any subsequent imminent task of the current
 * flow process will not be executed. Previously enqueued tasks would still be
 * in the process pipeline though.
 */
class FlowBreakException extends FlowException {}
