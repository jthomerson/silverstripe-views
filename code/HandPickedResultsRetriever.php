<?php

/* The simplest type of results retriever, this class allows a content manager
 * to manually select pages that should appear within the result set and order
 * them as they wish.
 *
 * @author Jeremy Thomerson <jeremy@thomersonfamily.com>
 * @copyright (c) 2012 Jeremy Thomerson <jeremy@thomersonfamily.com>
 * @package silverstripe-views
 * @subpackage code
 */
class HandPickedResultsRetriever extends ViewResultsRetriever {

   static $db = array();

   static $many_many = array(
      'Pages' => 'SiteTree',
   );

   static $many_many_extraFields = array(
      'Pages' => array(
         'SortOrder' => 'Int',
      ),
   );

   /* @see ViewResultsRetriever#getReadOnlySummary
    */
   public function getReadOnlySummary() {
      $html = '';
      $results = $this->Results();
      if ($results == null) {
         return $html;
      }
      foreach($results as $page) {
         $html .= '&nbsp&nbsp&nbsp&nbsp' . _t('Views.PageRef', 'Page reference') . ': [' . $page->ID . '] ' . $page->Title . '<br />';
      }
      return $html;
   }

   /* Deletes the associated many_many rows for hand-picked pages before
    * deleting this results retriever.
    *
    * @see DataObject#onBeforeDelete()
    */
   protected function onBeforeDelete() {
      parent::onBeforeDelete();
      parent::Pages()->removeAll();
   }

   /* Override the default "Pages" function built by SS to enable retrieval of
    * pages linked to this results retriever without a locale filter.  This
    * enables the hierarchy-traversal code that looks on the default locale's
    * translation for a view where it doesn't appear on the translation that a
    * user is actually viewing.
    *
    * Additionally this sorts them in the correct sort order (based on the
    * many_many_extraFields column).
    *
    * @return DataObjectSet or null the pages associated with this results retriever
    */
   public function Pages() {
      Translatable::disable_locale_filter();
      $pages = parent::Pages(null, 'SortOrder ASC');
      Translatable::enable_locale_filter();
      return $pages;
   }

   /* @see ViewResultsRetriever#Results
    */
   public function Results($maxResults = 0) {
      return $this->Pages();
   }

   /* @see ViewResultsRetriever#updateCMSFields
    */
   public function updateCMSFields(&$view, &$fields) {
      $picker = new ManyManyPickerField(
         $view,
         'ResultsRetriever.Pages',
         _t('Views.Pages.Label', 'Pages'),
         array(
            'ShowPickedInSearch' => false,
            'Sortable'           => true,
            'SortableField'      => 'SortOrder',
         )
      );
      $fields->addFieldToTab('Root.Main', $picker);
   }
}

