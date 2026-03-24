import axios from "axios";
import { getBranchSortOrders } from "./bookingOrdering";

/**
 * API сервис для работы с виджетом онлайн-записи
 * Интеграция с adminzrenie.ru API
 */
class BookingApiService {
  constructor() {
    this.baseURL = "https://adminzrenie.ru/api/v1";

    this.client = axios.create({
      baseURL: this.baseURL,
      timeout: 30000,
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
    });

    // Интерцептор для обработки ошибок
    this.client.interceptors.response.use(
      (response) => response,
      (error) => {
        console.error("Booking API Error:", error);
        return Promise.reject(error);
      }
    );
  }

  sortListByOrder(list, resolveOrder) {
    return [...(list || [])]
      .map((item, index) => ({
        item,
        index,
        sortOrder: resolveOrder(item),
      }))
      .sort((left, right) => {
        if (left.sortOrder === null && right.sortOrder === null) {
          return left.index - right.index;
        }

        if (left.sortOrder === null) {
          return 1;
        }

        if (right.sortOrder === null) {
          return -1;
        }

        if (left.sortOrder === right.sortOrder) {
          return left.index - right.index;
        }

        return left.sortOrder - right.sortOrder;
      })
      .map(({ item }) => item);
  }

  sortBranches(list, clinicId) {
    const clinicOrders = getBranchSortOrders()?.[String(clinicId)] || {};

    return this.sortListByOrder(list, (branch) => {
      const order = clinicOrders[String(branch?.id)];

      return Number.isFinite(Number(order)) ? Number(order) : null;
    });
  }

  replaceResponseData(payload, list) {
    if (Array.isArray(payload)) {
      return list;
    }

    if (payload && typeof payload === "object" && Array.isArray(payload.data)) {
      return {
        ...payload,
        data: list,
      };
    }

    return payload;
  }

  /**
   * Получить список городов
   * GET /api/v1/cities
   */
  async getCities() {
    try {
      const response = await this.client.get("/cities");
      return response.data;
    } catch (error) {
      throw this.handleError(error);
    }
  }

  /**
   * Получить список врачей по городу
   * GET /api/v1/cities/{city_id}/doctors
   */
  async getDoctorsByCity(cityId, birthDate = null) {
    try {
      const response = await this.client.get(`/cities/${cityId}/doctors`, {
        params: birthDate ? { birth_date: birthDate } : undefined,
      });
      return response.data;
    } catch (error) {
      throw this.handleError(error);
    }
  }

  /**
   * Получить слоты врача на дату
   * GET /api/v1/doctors/{doctor_id}/slots?date=YYYY-MM-DD&clinic_id=&branch_id=
   */
  async getDoctorSlots(doctorId, date, clinicId = null, branchId = null) {
    try {
      const response = await this.client.get(`/doctors/${doctorId}/slots`, {
        params: {
          date,
          clinic_id: clinicId || undefined,
          branch_id: branchId || undefined,
          _t: new Date().getTime(),
        },
      });
      return response.data;
    } catch (error) {
      throw this.handleError(error);
    }
  }

  /**
   * Получить агрегированную доступность слотов для календаря
   * GET /api/v1/booking/calendar-availability
   */
  async getCalendarAvailability({
    doctorId,
    dateFrom,
    dateTo,
    clinicId = null,
    branchId = null,
  }) {
    try {
      const response = await this.client.get(
        "/booking/calendar-availability",
        {
          params: {
            doctor_id: doctorId,
            date_from: dateFrom,
            date_to: dateTo,
            clinic_id: clinicId || undefined,
            branch_id: branchId || undefined,
          },
        }
      );
      return response.data;
    } catch (error) {
      throw this.handleError(error);
    }
  }

  /**
   * Проверить доступность слота (опционально, для 1С)
   * POST /api/v1/applications/check-slot
   */
  async checkSlot(slotData) {
    try {
      const response = await this.client.post(
        "/applications/check-slot",
        slotData
      );
      return response.data;
    } catch (error) {
      throw this.handleError(error);
    }
  }

  /**
   * Создать заявку на запись
   * POST /api/v1/applications
   */
  async createApplication(applicationData) {
    try {
      const response = await this.client.post("/applications", applicationData);
      return response.data;
    } catch (error) {
      throw this.handleError(error);
    }
  }

  /**
   * Получить клиники по городу
   * GET /api/v1/cities/{city_id}/clinics
   */
  async getClinicsByCity(cityId) {
    try {
      const response = await this.client.get(`/cities/${cityId}/clinics`);
      return response.data;
    } catch (error) {
      throw this.handleError(error);
    }
  }

  /**
   * Получить филиалы клиники
   * GET /api/v1/clinics/{clinic_id}/branches?city_id=
   */
  async getClinicBranches(clinicId, cityId = null) {
    try {
      const response = await this.client.get(`/clinics/${clinicId}/branches`, {
        params: cityId ? { city_id: cityId } : undefined,
      });
      const payload = response.data;
      const list = Array.isArray(payload?.data) ? payload.data : payload || [];

      return this.replaceResponseData(payload, this.sortBranches(list, clinicId));
    } catch (error) {
      throw this.handleError(error);
    }
  }

  /**
   * Получить врачей клиники
   * GET /api/v1/clinics/{clinic_id}/doctors
   */
  async getClinicDoctors(clinicId, birthDate = null, branchId = null) {
    try {
      const response = await this.client.get(`/clinics/${clinicId}/doctors`, {
        params: {
          birth_date: birthDate || undefined,
          branch_id: branchId || undefined,
        },
      });
      return response.data;
    } catch (error) {
      throw this.handleError(error);
    }
  }

  /**
   * Получить локальные данные врачей сайта по UUID (external_id)
   * GET /api/booking/doctors?uuids=a,b,c
   */
  async getSiteDoctorsByUuids(uuids = []) {
    try {
      const normalized = Array.from(
        new Set(
          (uuids || [])
            .map((uuid) => String(uuid || "").trim().toLowerCase())
            .filter(Boolean)
        )
      );

      if (!normalized.length) {
        return { data: [] };
      }

      const response = await axios.get("/api/booking/doctors", {
        params: {
          uuids: normalized.join(","),
        },
      });

      return response.data;
    } catch (error) {
      throw this.handleError(error);
    }
  }

  /**
   * Получить информацию о враче
   * GET /api/v1/doctors/{doctor_id}
   */
  async getDoctor(doctorId) {
    try {
      const response = await this.client.get(`/doctors/${doctorId}`);
      return response.data;
    } catch (error) {
      throw this.handleError(error);
    }
  }

  /**
   * Обработка ошибок API
   */
  handleError(error) {
    if (error.response) {
      const { status, data } = error.response;

      switch (status) {
        case 400:
          return new Error(data.message || "Неверный запрос");
        case 404:
          return new Error("Данные не найдены");
        case 422:
          return {
            message: "Ошибка валидации данных",
            errors: data.errors || {},
            status: 422,
          };
        case 500:
          return new Error("Ошибка сервера. Попробуйте позже");
        default:
          return new Error(data.message || "Произошла ошибка");
      }
    } else if (error.request) {
      return new Error("Нет связи с сервером. Проверьте интернет-соединение");
    } else {
      return new Error(error.message || "Произошла ошибка");
    }
  }
}

export default new BookingApiService();
