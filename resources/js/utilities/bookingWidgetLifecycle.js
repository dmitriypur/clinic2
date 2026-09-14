export function createBookingWidgetLifecycleState() {
  return {
    bookingWidgetV3Mounted: false,
    bookingWidgetV3Active: false,
    bookingWidgetV3Target: null,
    bookingWidgetV3Mode: null,
    bookingWidgetV3LaunchContext: null,
  };
}

export function activateBookingWidgetV3(
  state,
  { target = null, mode = null, launchContext = null } = {}
) {
  state.bookingWidgetV3Mounted = true;
  state.bookingWidgetV3Target = target;
  state.bookingWidgetV3LaunchContext = launchContext;
  state.bookingWidgetV3Mode = mode;
  state.bookingWidgetV3Active = true;
}

export function closeBookingWidgetV3(state) {
  state.bookingWidgetV3Active = false;
  state.bookingWidgetV3Target = null;
  state.bookingWidgetV3Mode = null;
  state.bookingWidgetV3LaunchContext = null;
}
