<?php

/* A ViewHost is a DataObjectDecorator that can be added to DataObjects to
 * allow them to have view definitions added to them.  With the default module
 * configuration all SiteTree nodes have the ViewHost DOD added to them.
 *
 * @todo test adding this to SiteConfig to allow for the definition of site-
 *       wide views available from all pages.  The view traversal code will
 *       need to be modified to look at SiteConfig after exhausting all other
 *       options (and look at both translations of SiteConfig like it does for
 *       pages)
 *
 * @author Jeremy Thomerson <jeremy@thomersonfamily.com>
 * @copyright (c) 2012 Jeremy Thomerson <jeremy@thomersonfamily.com>
 * @package silverstripe-views
 * @subpackage code
 */
class ViewHost extends DataObjectDecorator {

   /* @see DataObjectDecorator#extraStatics
    */
   function extraStatics() {
      return array(
         'has_many' => array(
            'Views' => 'View',
         ),
      );
   }

   /* Accessor for retrieving all views attached to the owning data object.
    *
    * @todo why did $this->owner->Views() not work?
    */
   public function getAllViews() {
      return DataObject::get('View', '"HostID" = ' . $this->owner->ID);
   }

   /* Used by templates in a control block to retrieve a view by name.  The
    * maximum number of results can optionally be passed in (default: infinite)
    * Additionally, a boolean can be passed in to indicate whether or not the
    * hierarchy should be traversed to find the view on translations and
    * parents (default: true).
    *
    * @param string $name the name of the view to find
    * @param int $max the max results (or 0 for infinite) (optional: default 0)
    * @param boolean $traverse traverse hierarchy looking for view? (default: true)
    * @return View the found view or null if not found
    */
   public function GetView($name, $max = 0, $traverse = true) {
      // ATTEMPT 1: Do I have the view on this page?
      $view = $this->owner->getViewWithoutTraversal($name);

      if ($view == null && $traverse) {
         $defaultLocale = class_exists('Translatable') ? Translatable::default_locale() : null;

         // ATTEMPT 2: if we're translatable get the page of the default locale and see if it has the view
         if ($this->owner->hasExtension('Translatable') && $this->owner->Locale != $defaultLocale) {
            $master = $this->owner->getTranslation($defaultLocale);
            $view = ($master != null && $master->hasExtension('ViewHost')) ? $master->getViewWithoutTraversal($name) : null;
         }

         // ATTEMPT 3: go to my parent page and try to get the view (and allow it to continue traversing)
         if ($view == null && $this->owner->ParentID != 0 && ($parent = $this->owner->Parent()) != null && $parent->hasExtension('ViewHost')) {
            return $parent->GetView($name, $max, $traverse);
         }
      }

      return $view;
   }

   /* Internal function used by GetView to actually implement the non-recursive
    * portion of the view searching functionality.  This function checks only
    * its owner object to see if it contains the given view.
    *
    * @see GetView()
    * @param string $name the name of the view to find
    * @return View the found view or null if not found
    */
   public function getViewWithoutTraversal($name) {
      $allViews = $this->owner->getAllViews();
      if ($allViews == null) {
         return null;
      }
      foreach ($allViews as $view) {
         if ($view->Name == $name) {
            return $view;
         }
      }
      return null;
   }

   /* Used by templates in a conditional block to see if there is a view with a
    * given name defined on this page (or, if traversing, a translation or
    * parent)
    *
    * @param string $name the name of the view to find
    * @param int $max the max results (or 0 for infinite) (optional: default 0)
    * @param boolean $traverse traverse hierarchy looking for view? (default: true)
    * @return View the found view or null if not found
    */
   public function HasView($name, $max = 0, $traverse = true) {
      return ($this->GetView($name, $max, $traverse) != null);
   }

   /* Used by templates in a conditional block to see if there is a view with a
    * given name defined on this page (or, if traversing, a translation or
    * parent) AND the view has results in the language of the page that is
    * being viewed.
    *
    * @param string $name the name of the view to find
    * @param int $max the max results (or 0 for infinite) (optional: default 0)
    * @param boolean $traverse traverse hierarchy looking for view? (default: true)
    * @return View the found view or null if not found
    */
   public function HasViewWithTranslatedResults($name, $max = 0, $traverse = true) {
      $view = $this->GetView($name, $max, $traverse);
      if ($view == null) {
         return false;
      }
      $firstResult = $view->TranslatedResults(1);
      return !empty($firstResult);
   }

   /* @see DataObjectDecorator#updateCMSFields
    */
   public function updateCMSFields(FieldSet &$fields) {
      $viewsTable = new HasManyComplexTableField(
         $this->owner,
         'Views',
         'View',
         array(
            'ReadOnlySummary' => 'Name',
         ),
         'getCMSFields',
         sprintf('"View"."HostID" = %d', $this->owner->ID)
      );
      $viewsTable->setAddTitle('A View');

      $fields->addFieldToTab('Root.Views', $viewsTable);
   }

}
