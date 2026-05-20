'use strict';

jQuery('.click_edit').editable(function (value, settings) {
  return (value);
}, {
  submit: 'OK',
  tooltip: 'Click to edit...'
});
function langOk(ok) {
  jQuery(ok).parent('form').parent('p').css('color', '#cc324b');
  return true;
}  