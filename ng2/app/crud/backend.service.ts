import {Injectable} from '@angular/core';
import {Http, Response, Headers, RequestOptions, URLSearchParams} from "@angular/http";
import {Observable} from "rxjs";
import {ListResponse} from "./list-response";
import {environment} from "../../environments/environment";

const ROW_PER_PAGE = "10";

@Injectable()
export class BackendService {

    constructor(private _http: Http) {}

    form(entity:string):Observable<ListResponse<any>> {
        return this.http.get(BackendService.buildUrl(entity) + 'form')
            .map(BackendService.extractData)
            .catch(BackendService.handleError);
    }

    all(entity: string, page: string = "1"): Observable<ListResponse<any>> {
        let params: URLSearchParams = new URLSearchParams();
        params.set('page', page);
        params.set('limit', ROW_PER_PAGE);
        return this.http.get(BackendService.buildUrl(entity), {search: params})
            .map(BackendService.extractData)
            .catch(BackendService.handleError);
    }

    add(entity: string, item: any): Observable<any> {
        let headers = new Headers({'Content-Type': 'application/json'});
        let options = new RequestOptions({headers: headers});
        return this.http.post(BackendService.buildUrl(entity), item, options)
            .map(BackendService.extractData)
            .catch(BackendService.handleError);
    }

    remove(entity: string, item: any): Observable<any> {
        return this.http.delete(BackendService.buildUrl(entity) + item.id, {})
            .map(() => item)
            .catch(BackendService.handleError);
    }

    private static extractData(res: Response) {
        return res.json();
    }

    private static handleError(error: Response | any) {
        // In a real world app, we might use a remote logging infrastructure
        let errMsg: string;
        if (error instanceof Response) {
            const body = error.json() || '';
            const err = body.error || JSON.stringify(body);
            errMsg = `${error.status} - ${error.statusText || ''} ${err}`;
        } else {
            errMsg = error.message ? error.message : error.toString();
        }
        console.error(errMsg);
        return Observable.throw(errMsg);
    }

    get http(): Http {
        return this._http;
    }

    static buildUrl(entity: string) {
        return environment.baseUrl + entity + "/";
    }
}
