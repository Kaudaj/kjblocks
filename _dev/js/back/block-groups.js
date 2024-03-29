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

$(() => {
  initForm();
  initGrid();
});

function initForm() {
  window.prestashop.component.initComponents(['TranslatableInput']);

  // Fix select2 field taking value by default
  if ($('#block_groups_hooks').length) {
    $('#block_groups_hooks option')[0].remove();
  }
}

function initGrid() {
  const blocksGrid = new window.prestashop.component.Grid('block_groups');

  const gridExtensions = [
    new window.prestashop.component.GridExtensions.SortingExtension(),
    new window.prestashop.component.GridExtensions.SubmitRowActionExtension(),
    new window.prestashop.component.GridExtensions.LinkRowActionExtension(),
    new window.prestashop.component.GridExtensions.PositionExtension(),
    new window.prestashop.component.GridExtensions.FiltersResetExtension(),
  ];

  gridExtensions.forEach((extension) => {
    blocksGrid.addExtension(extension);
  });
}
