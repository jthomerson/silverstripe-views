<?php

/* A view is a definition of an object that retrieves pages from the CMS.  It
 * can also be conceptualized as a placeholder in a template where one or more
 * pages/nodes are referenced.  The actual content that appears in these place-
 * holders is defined in a view that is added to a SiteTree node through the
 * UI.  This gives your content managers the ability to dynamically change the
 * content that is featured in your templates.
 *
 * @author Jeremy Thomerson <jeremy@thomersonfamily.com>
 * @copyright (c) 2012 Jeremy Thomerson <jeremy@thomersonfamily.com>
 * @package silverstripe-views
 * @subpackage code
 */
class View extends DataObject {

   static $db = array(
      'Name'        => 'VARCHAR(32)',
   );

   static $has_one = array(
      'ResultsRetriever' => 'ViewResultsRetriever',
      'Host'             => 'DataObject',
   );

   static $default_sort = 'Name';

   /* @see DataObject#getCMSFields()
    */
   function getCMSFields() {
      $fields = new FieldSet(
         new TabSet('Root',
            new Tab('Main',
               new TextField('Name', _t('Views.Name.Label', 'Name'))
            )
         )
      );

      $rr = $this->ResultsRetriever();
      if ($this->ID && $rr != null && get_class($rr) != 'ViewResultsRetriever') {
         // only allow editing of actual results retriever on non-transient views
         $rr->updateCMSFields($this, $fields);
      }

      return $fields;
   }

   /* Used in the current configuration of the views UI */
   public function getReadOnlySummary() {
      $html = '<strong style="font-size: 1.1em;">' . $this->Name . '</strong> <em>(' . get_class($this->ResultsRetriever()) . ')</em><br />';
      $html .= '<span style="font-size: 0.9em;">' . $this->ResultsRetriever()->getReadOnlySummary() . '</span>';
      return $html;
   }

   /* Used by ComplexTableField to validate objects added in the CMS UI
    *
    * @todo add a unique-per-hosting-object validation rule to "Name"
    */
   public function getValidator() {
      return new RequiredFields('Name', 'ResultsRetrieverID');
   }

   /* Deletes the associated results retriever before deleting this view.
    *
    * @see DataObject#onBeforeDelete()
    */
   protected function onBeforeDelete() {
      parent::onBeforeDelete();

      $this->ResultsRetriever()->delete();
   }

   /* Used in templates to get the correct translation (if available) of
    * results retrieved by the results retriever for this view.
    *
    * @todo better documentation for this function
    * @todo test $maxResults functionality... by passing it to the results
    *            retriever we are really breaking this.  The results retriever
    *            might return 5 of 10 actual results (if we pass 5), and we
    *            might only have three translations of those five results.  But
    *            if we retrieved all results and then checked for translations
    *            we might be able to get up to our real max.
    *
    * @param int $maxResults maximum number of results to retriever, or 0 for infinite (default 0)
    * @return DataObjectSet the results in the current locale or null if none found
    */
   public function TranslatedResults($maxResults = 0) {
      $results = $this->ResultsRetriever()->Results($maxResults);
      if (empty($results)) {
         return null;
      }
      $currentPage = Director::get_current_page();
      if ($currentPage == null || !$currentPage->hasExtension('Translatable')) {
         return $results;
      }
      $locale = $currentPage->Locale;
      $translatedResults = array();
      foreach ($results as $result) {
         $translatedResult = $result->hasExtension('Translatable') ? $result->getTranslation($locale) : null;
         if ($translatedResult != null) {
            $translatedResults[] = $translatedResult;
         }
      }
      return empty($translatedResults) ? null : new DataObjectset($translatedResults);
   }

}
