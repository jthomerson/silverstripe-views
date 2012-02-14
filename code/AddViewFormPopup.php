<?php

/* A custom popup form for adding a new (transient instance) view.
 *
 * @author Jeremy Thomerson <jeremy@thomersonfamily.com>
 * @copyright (c) 2012 Jeremy Thomerson <jeremy@thomersonfamily.com>
 * @package silverstripe-views
 * @subpackage code
 */
class AddViewFormPopup extends ComplexTableField_Popup {

   /* @see ComplexTableField_Popup#__construct()
    */
   function __construct($controller, $name, $fields, $validator, $readonly, $dataObject) {
      parent::__construct($controller, $name, $fields, $validator, $readonly, $dataObject);

      if (!$dataObject->ID) {
         // This is a new instance of a View - so we change from "edit" logic to "add"

         $types = ClassInfo::subclassesFor('ViewResultsRetriever');
         array_shift($types); // remove ViewResultsRetriever (first element)

         $retrieverType = new DropDownField(
            'ResultsRetrieverType',
            _t('Views.ResultsRetrieverType.Label', 'Results Retriever Type'),
            $types
         );

         // add the "retriever type" field when adding a new view
         $fields->addFieldToTab('Root.Main', $retrieverType);

         // and override the actions to use our custom function addView (rather than standard save behavior)
         $this->setActions(new FieldSet(new FormAction('addView', _t('Views.Add.Label', 'Add View'))));
      }
   }

   /* Action function called by the only button on the "add view" form. Creates
    * a new view based on the form inputs and persists the view (and associated
    * results retriever) to the database.
    *
    * @todo rather than using $data direction this should be using
    *       $this->getData (see Form class docs)
    * @todo test that this the controller/form is still testing for CSRF etc
    * @todo do we need something like "allowed_actions" to prohibit other
    *       functions from being called?
    *
    * @param array $data the form data that was submitted
    * @param Form $form the form being submitted
    * @return void
    */
   public function addView($data, $form) {
      $view = new View();
      $view->Name = $data['Name'];
      $rr = new $data['ResultsRetrieverType']();
      $rr->write();
      $view->ResultsRetrieverID = $rr->ID;
      $view->HostID = $data['ctf']['sourceID'];
      $view->write();

      return $this->afterViewAdded($view, $form);
   }

   /* Handles displaying a success message to the user and re-painting the form
    *
    * @param View &$view the view that was added
    * @param Form &$form the form being submitted
    * @return void
    */
   private function afterViewAdded(&$view, &$form) {
      $referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
      $closeLink = sprintf(
         '<small><a href="%s" onclick="javascript:window.top.GB_hide(); return false;">(%s)</a></small>',
         $referrer,
         _t('AddViewFormPopup.CLOSEPOPUP', 'Close Popup')
      );

      $editLink = sprintf(
         '<small><a href="%s">(%s)</a></small>',
         Controller::join_links($this->Controller()->Link(), 'item', $view->ID, 'edit'),
         _t('AddViewFormPopup.EDIT', 'Edit now')
      );

      $message = sprintf(
         _t('AddViewFormPopup.SUCCESSADD', 'Saved new view named "%s" %s %s'),
         $view->Name,
         $editLink,
         $closeLink
      );

      $form->sessionMessage($message, 'good');

      Director::redirectBack();
   }

}
