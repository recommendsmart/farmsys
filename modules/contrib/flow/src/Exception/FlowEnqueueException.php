<?php

namespace Drupal\flow\Exception;

/**
 * Thrown when a task operation needs to halt and to be continued in queue.
 *
 * This is meant for operations that may continue on top of an intermediate
 * result. For example, sending mails to a list of users may be stopped after
 * sending a mail to the user having the 10th position of a user list, and
 * then continue starting at the 11th position handled by a background process,
 * which works on the "flow_task" queue.
 */
class FlowEnqueueException extends FlowException {}
