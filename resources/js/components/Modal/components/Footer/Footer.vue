<template>
  <div class="flex self-end items-center w-full p-4">
    <div class="w-full">
      <div class="flex items-center">
        <div class="flex-auto">
          <slot/>
        </div>
      </div>
      <!-- <Stack alignment="center">
        <StackItem fill>
          <slot/>
        </StackItem> -->
      <!--{actions}-->
      <!-- <ButtonGroup v-if="primaryAction || secondaryActions"> -->
      <!--{secondaryActionButtons}-->
      <!--          <template v-if="secondaryActions">-->
      <!--            <Button-->
      <!--              v-for="(action, index) in secondaryActions"-->
      <!--              :key="`modalSecondaryAtion${index}`"-->
      <!--              v-bind="action"-->
      <!--            >-->
      <!--              {{ action.content }}-->
      <!--            </Button>-->
      <!--          </template>-->
      <!--{primaryActionButton}-->
      <!--          <Button v-if="primaryAction" primary v-bind="primaryAction"></Button>-->
      <!-- </ButtonGroup> -->
      <!-- </Stack> -->
    </div>
  </div>
</template>

<script>
// import {Button} from '../../../Button';
// import {ButtonGroup} from '../../../ButtonGroup';
// import {Stack, StackItem} from '../../../Stack';

export default {
  components: {
    // Button,
    // ButtonGroup, Stack, StackItem
  },
  props: {
    primaryAction: {
      type: Object,
      default: null,
    },
    secondaryActions: {
      type: Array,
      default: null,
    },
  },
}
</script>
