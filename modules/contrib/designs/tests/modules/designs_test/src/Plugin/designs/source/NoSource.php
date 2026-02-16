<?php

namespace Drupal\designs_test\Plugin\designs\source;

/**
 * The design source for testing no regions and no custom content.
 *
 * @DesignSource(
 *   id = "designs_test_none",
 *   label = @Translation("No extras"),
 *   usesRegionsForm = FALSE,
 *   usesCustomContent = FALSE
 * )
 */
class NoSource extends BaseSource {

}
