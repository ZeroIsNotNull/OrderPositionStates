import PositionStateService from '../position.state.service';

Shopware.Service().register('positionStateService', (container) => {
    const initContainer = Shopware.Application.getContainer('init');
    return new PositionStateService(initContainer.httpClient, container.loginService);
});
