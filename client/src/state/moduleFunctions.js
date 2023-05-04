import store from './store';
import { setModules } from './moduleActions';
import authAxios from '../common/authAxios';

export function fetchModules() {
    authAxios.get('/api/admin/modules.php')
        .then(res => {
            store.dispatch(setModules(res.data.modules));
        })
        .catch(error => {
            let message = {
                severity: "danger",
                text: "The list of modules could not be downloaded."
            };
            store.dispatch(setModules({ list: [] }, message));
        }
    );
}