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

/* eslint-disable camelcase */

/* global kjblocks_defaultDescriptionText */

const {$} = window;

export default class BlockTypeModal {
  constructor(formName) {
    this.formName = formName;
  }

  init() {
    this.$modal = $('#block-type-modal');

    this.$modulesStep = this.$modal.find('.modules-step');
    this.$blocksStep = this.$modal.find('.blocks-step');
    this.$backButton = this.$modal.find('.btn.back');
    this.$blockTypeInput = $(`#${this.formName}_type`);
    this.currentValue = this.$blockTypeInput.val();

    this.$modulesStep.on('click', '.modules li', (event) => {
      const $item = BlockTypeModal.getItem(event);

      this.$blocksStep.find('.blocks').html($item.find('.blocks').html());
      this.$blocksStep
        .find(`li[data-value="${this.currentValue}"]`)
        .addClass('selected');

      this.switchStep();
    });

    this.$blocksStep.on('click', '.blocks li', (event) => {
      const $item = BlockTypeModal.getItem(event);

      const value = $item.data('value');

      this.$blocksStep
        .find(`li[data-value="${this.currentValue}"]`)
        .removeClass('selected');
      this.currentValue = value;
      $item.addClass('selected');
    });

    this.$blocksStep.on('mouseover', '.blocks li', (event) => {
      const $item = BlockTypeModal.getItem(event);

      this.$blocksStep
        .find('.current-description')
        .html($item.find('.description').html());
    });

    this.$blocksStep.on('mouseleave', '.blocks li', () => {
      this.$blocksStep
        .find('.current-description')
        .html(kjblocks_defaultDescriptionText);
    });

    this.$modal.on('click', '.btn.save', () => {
      this.$blockTypeInput.val(this.currentValue);
      this.$blockTypeInput.trigger('change');

      this.switchStep();
      this.$modal.modal('hide');
    });

    this.$backButton.on('click', () => {
      this.switchStep();
    });

    this.$modal.on('hide.bs.modal', () => {
      if (this.$blocksStep.is(':visible')) {
        this.switchStep();
      }

      this.currentValue = this.$blockTypeInput.val();
    });
  }

  switchStep() {
    this.$modulesStep.toggle();
    this.$blocksStep.toggle();
    this.$backButton.toggle();
  }

  static getItem(event) {
    let $target = $(event.target);

    if (!$target.is('li')) {
      $target = $target.closest('li');
    }

    return $target;
  }
}
