export function getDoctorSelectSortOrders() {
  return (
    window.config?.booking?.doctorSelectSortOrders ||
    window.config?.booking?.doctorSortOrders ||
    {}
  );
}

export function getClinicDoctorSortOrders() {
  return window.config?.booking?.clinicDoctorSortOrders || {};
}

export function getBranchSortOrders() {
  return window.config?.booking?.branchSortOrders || {};
}
