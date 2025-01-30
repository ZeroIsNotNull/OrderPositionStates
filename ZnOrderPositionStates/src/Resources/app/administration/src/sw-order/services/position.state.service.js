const { ApiService } = Shopware.Classes;

class PositionStateService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'orderpositionstates') {
        super(httpClient, loginService, apiEndpoint);
        this.name = 'positionStateService';
    }

    setPositionState(lineItemId, stateId, additionalParams = {}, additionalHeaders = {}) {
        const route = `/_action/orderpositionstates/set-position-state/${lineItemId}/${stateId}`;
        const headers = Object.assign(this.getBasicHeaders(additionalHeaders));
        return this.httpClient
            .patch(route, {}, {additionalParams, headers}
            ).then((response) => {
                console.log(response);
                return ApiService.handleResponse(response)
            }).catch((error) => {
                console.log(error);
            });
    }

    async getPositionState(lineItemId, additionalParams = {}, additionalHeaders = {}) {
        const route = `/_action/orderpositionstates/get-position/${lineItemId}`;
        const headers = Object.assign(this.getBasicHeaders(additionalHeaders));
        try {
            let response = await this.httpClient.get(route, { headers });
            return ApiService.handleResponse(response);
        } catch (error) {
            console.log(error);
            throw error;
        }
    }
}
export default PositionStateService;
