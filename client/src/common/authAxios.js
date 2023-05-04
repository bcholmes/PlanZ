// The idea of this axios instance is that it should be used when we expect that the session is authorized.
// If an unauthorized status code returns (401), we can redirect the use to the login page.

import axios from 'axios';
import { redirectToLogin } from './redirectToLogin';

const authAxios = axios.create();

authAxios.interceptors.response.use(
    (response) => {
        return response;
    },
    (error) => {
        if (error?.response?.status === 401) {
            redirectToLogin();
        }
        return Promise.reject(error);
    }
);

export default authAxios;