import axios from 'axios';

/**
 * API сервис для работы с виджетом онлайн-записи
 * Интеграция с adminzrenie.ru API
 */
class BookingApiService {
  constructor() {
    // Используем тестовый API по умолчанию, можно переключить на prod
    this.baseURL = process.env.NODE_ENV === 'production' 
      ? 'https://adminzrenie.ru/api/v1'
      : 'https://app.fondzrenie.ru/api/v1';
    
    this.client = axios.create({
      baseURL: this.baseURL,
      timeout: 30000,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
    });

    // Интерцептор для обработки ошибок
    this.client.interceptors.response.use(
      (response) => response,
      (error) => {
        console.error('Booking API Error:', error);
        return Promise.reject(error);
      }
    );
  }

  /**
   * Получить список городов
   * GET /api/v1/cities
   */
  async getCities() {
    try {
      const response = await this.client.get('/cities');
      return response.data;
    } catch (error) {
      throw this.handleError(error);
    }
  }

  /**
   * Получить список врачей по городу
   * GET /api/v1/cities/{city_id}/doctors
   */
  async getDoctorsByCity(cityId) {
    try {
      const response = await this.client.get(`/cities/${cityId}/doctors`);
      return response.data;
    } catch (error) {
      throw this.handleError(error);
    }
  }

  /**
   * Получить слоты врача на дату
   * GET /api/v1/doctors/{doctor_id}/slots?date=YYYY-MM-DD
   */
  async getDoctorSlots(doctorId, date) {
    try {
      const response = await this.client.get(`/doctors/${doctorId}/slots`, {
        params: { date },
      });
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
      const response = await this.client.post('/applications/check-slot', slotData);
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
      const response = await this.client.post('/applications', applicationData);
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
      // Сервер ответил с ошибкой
      const { status, data } = error.response;
      
      switch (status) {
        case 400:
          return new Error(data.message || 'Неверный запрос');
        case 404:
          return new Error('Данные не найдены');
        case 422:
          // Ошибки валидации
          return {
            message: 'Ошибка валидации данных',
            errors: data.errors || {},
            status: 422,
          };
        case 500:
          return new Error('Ошибка сервера. Попробуйте позже');
        default:
          return new Error(data.message || 'Произошла ошибка');
      }
    } else if (error.request) {
      // Запрос был отправлен, но ответа не получено
      return new Error('Нет связи с сервером. Проверьте интернет-соединение');
    } else {
      // Ошибка при настройке запроса
      return new Error(error.message || 'Произошла ошибка');
    }
  }
}

// Экспортируем singleton
export default new BookingApiService();
