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

import BlockType from './components/block-type';
import BlockTypeModal from './components/block-type-modal';

const {$} = window;

$(() => {
  initForm();
  initGrid();
});

function initForm() {
  window.prestashop.component.initComponents([
    'TranslatableInput',
    'TinyMCEEditor',
    'MultistoreConfigField',
  ]);

  const blockType = new BlockType('block_type');
  blockType.init();

  const blockTypeModal = new BlockTypeModal('block_type');
  blockTypeModal.init();

  const $form = $('#block_edit, #block_create');

  $form.on('KJBlockTypeChanged', (event, data) => {
    switch (data.newType) {
      case 'text':
        resetTinyMCEEditor();
        break;
      default:
    }
  });
}

function resetTinyMCEEditor() {
  window.tinyMCE.remove();
  window.prestashop.instance.tinyMCEEditor = undefined;
  window.prestashop.component.initComponents(['TinyMCEEditor']);
}

function initGrid() {
  const blocksGrid = new window.prestashop.component.Grid('blocks');

  const gridExtensions = [
    new window.prestashop.component.GridExtensions.SortingExtension(),
    new window.prestashop.component.GridExtensions.SubmitRowActionExtension(),
    new window.prestashop.component.GridExtensions.LinkRowActionExtension(),
    new window.prestashop.component.GridExtensions.PositionExtension(),
    new window.prestashop.component.GridExtensions.FiltersResetExtension(),
    new window.prestashop.component.GridExtensions.ColumnTogglingExtension(),
  ];

  gridExtensions.forEach((extension) => {
    blocksGrid.addExtension(extension);
  });
}
