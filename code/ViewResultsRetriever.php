<?php

/* Base class for all classes which provide results to a View.
 *
 * @author Jeremy Thomerson <jeremy@thomersonfamily.com>
 * @copyright (c) 2012 Jeremy Thomerson <jeremy@thomersonfamily.com>
 * @package silverstripe-views
 * @subpackage code
 */
class ViewResultsRetriever extends DataObject {

   /* All subclasses should implement this function, which provides a read-only
    * summary of the results retriever in an HTML format.  This can be used to
    * display to the user when describing the View that uses this
    * ResultsRetriever.
    *
    * @return string HTML string describing this results retriever.
    */
   public function getReadOnlySummary() {
      return 'The ' . get_class($this) . ' class needs to implement getReadOnlySummary().';
   }

   /* All subclasses must implement this function, which is the primary
    * interface to the outside world.  When a view is requested, this function
    * will be called and expected to return a DataObjectSet of results or null
    * if no results could be retrieved.
    *
    * @param int $maxResults the maximum number of results to return
    * @return DataObjectSet|null the results or null if none found
    */
   public function Results($maxResults = 0) {
      throw new UnsupportedOperationException('The ' . get_class($this) . ' class needs to implement Results(int).');
   }

   /* All subclasses should implement this function, which provides them a way
    * of adding fields to the "add/edit view" CMS form.  These fields will be
    * what the user uses to modify this results retriever.
    *
    * @param View reference to the view that contains this results retriever
    * @param FieldSet the fields for this view form
    */
   public function updateCMSFields(&$view, &$fields) {
      // no default operation
   }

}
