import {
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from '@/wayfinder';

type Method = 'get' | 'post';

const route = <TMethod extends Method>(
    url: string,
    method: TMethod,
    options?: RouteQueryOptions,
): RouteDefinition<TMethod> =>
    ({
        url: url + queryParams(options),
        method,
    }) as unknown as RouteDefinition<TMethod>;

const form = <TMethod extends Method>(
    url: string,
    method: TMethod,
    options?: RouteQueryOptions,
): RouteFormDefinition<TMethod> => ({
    action: url + queryParams(options),
    method,
});

export const qrCode = (options?: RouteQueryOptions): RouteDefinition<'get'> =>
    route('/user/two-factor-qr-code', 'get', options);

qrCode.form = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> =>
    form('/user/two-factor-qr-code', 'get', options);

export const secretKey = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => route('/user/two-factor-secret-key', 'get', options);

secretKey.form = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> =>
    form('/user/two-factor-secret-key', 'get', options);

export const recoveryCodes = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> =>
    route('/user/two-factor-recovery-codes', 'get', options);

recoveryCodes.form = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> =>
    form('/user/two-factor-recovery-codes', 'get', options);

export const regenerateRecoveryCodes = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> =>
    route('/user/two-factor-recovery-codes', 'post', options);

regenerateRecoveryCodes.form = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> =>
    form('/user/two-factor-recovery-codes', 'post', options);

export const confirm = (options?: RouteQueryOptions): RouteDefinition<'post'> =>
    route('/user/confirmed-two-factor-authentication', 'post', options);

confirm.form = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> =>
    form('/user/confirmed-two-factor-authentication', 'post', options);
