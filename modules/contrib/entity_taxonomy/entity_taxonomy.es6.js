/**
 * @file
 * entity_taxonomy behaviors.
 */

(function ($, Drupal) {
  /**
   * Reorder entity_taxonomy terms.
   *
   * This behavior is dependent on the tableDrag behavior, since it uses the
   * objects initialized in that behavior to update the row.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the drag behavior to an applicable table element.
   */
  Drupal.behaviors.termDrag = {
    attach(context, settings) {
      const backStep = settings.entity_taxonomy.backStep;
      const forwardStep = settings.entity_taxonomy.forwardStep;
      // Get the entity_taxonomy tableDrag object.
      const tableDrag = Drupal.tableDrag.entity_taxonomy;
      const $table = $('#entity_taxonomy');
      const rows = $table.find('tr').length;

      // When a row is swapped, keep previous and next page classes set.
      tableDrag.row.prototype.onSwap = function (swappedRow) {
        $table
          .find('tr.entity_taxonomy-term-preview')
          .removeClass('entity_taxonomy-term-preview');
        $table
          .find('tr.entity_taxonomy-term-divider-top')
          .removeClass('entity_taxonomy-term-divider-top');
        $table
          .find('tr.entity_taxonomy-term-divider-bottom')
          .removeClass('entity_taxonomy-term-divider-bottom');

        const tableBody = $table[0].tBodies[0];
        if (backStep) {
          for (let n = 0; n < backStep; n++) {
            $(tableBody.rows[n]).addClass('entity_taxonomy-term-preview');
          }
          $(tableBody.rows[backStep - 1]).addClass('entity_taxonomy-term-divider-top');
          $(tableBody.rows[backStep]).addClass('entity_taxonomy-term-divider-bottom');
        }

        if (forwardStep) {
          for (let k = rows - forwardStep - 1; k < rows - 1; k++) {
            $(tableBody.rows[k]).addClass('entity_taxonomy-term-preview');
          }
          $(tableBody.rows[rows - forwardStep - 2]).addClass(
            'entity_taxonomy-term-divider-top',
          );
          $(tableBody.rows[rows - forwardStep - 1]).addClass(
            'entity_taxonomy-term-divider-bottom',
          );
        }
      };
    },
  };
})(jQuery, Drupal);
