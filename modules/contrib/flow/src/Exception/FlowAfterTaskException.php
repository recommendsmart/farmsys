<?php

namespace Drupal\flow\Exception;

/**
 * Tells the Flow engine that this task needs to do work afterwards.
 *
 * This exception is to be thrown when the task needs to continue after the
 * task mode operation was completed. Different values of information may be
 * available during and after the task mode operation. For example, when a new
 * content item is being created, the content ID is only available after it got
 * saved.
 */
class FlowAfterTaskException extends FlowException {}
