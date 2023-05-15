/**
 * Copyright since 2019 Kaudaj
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to info@kaudaj.com so we can send you a copy immediately.
 *
 * @author    Kaudaj <info@kaudaj.com>
 * @copyright Since 2019 Kaudaj
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */
const {$} = window;

export default class BlockType {
  constructor(formName) {
    this.formName = formName;
  }

  init() {
    $(`#${this.formName}_type`).on('change', (event) => {
      const $target = $(event.target);
      const $form = $target.closest('form');

      const data = {};
      data[$target.attr('name')] = $target.val();

      $.ajax({
        url: $form.attr('action'),
        type: $form.attr('method'),
        data,
        complete: (html) => {
          const optionsInputSelector = `#${this.formName}_options`;
          const $oldField = $(optionsInputSelector);

          const $response = $(html.responseText);
          const $newField = $response.find(optionsInputSelector);

          $oldField.replaceWith($newField);

          $form.trigger('KJBlockTypeChanged', {
            newType: $target.val(),
          });
        },
      });
    });
  }
}
