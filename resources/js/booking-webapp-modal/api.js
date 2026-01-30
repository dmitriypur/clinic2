import axios from "axios";

export function createApiClient(baseURL) {
  return axios.create({
    baseURL,
    timeout: 12000,
    headers: {
      Accept: "application/json",
      "Content-Type": "application/json",
    },
  });
}
