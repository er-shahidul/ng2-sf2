import { Injectable } from '@angular/core';
import {Http, Response, Headers, RequestOptions, URLSearchParams} from "@angular/http";
import {Observable} from "rxjs";
import {User} from "./user";
import {environment} from "../../environments/environment";
import {UserListResponse} from "./user-list-response";

const ROW_PER_PAGE = "10";

@Injectable()
export class UserService {
  private _usersUrl = 'users/';

  constructor(private _http: Http) { }

  getUsers (page:string = "1"): Observable<UserListResponse> {
    let params: URLSearchParams = new URLSearchParams();
    params.set('page', page);
    params.set('limit', ROW_PER_PAGE);
    return this.http.get(this.usersUrl, {search: params})
        .map(UserService.extractData)
        .catch(UserService.handleError);
  }

  addUser (user: User): Observable<User> {
    let headers = new Headers({ 'Content-Type': 'application/json' });
    let options = new RequestOptions({ headers: headers });
    return this.http.post(this.usersUrl, user, options)
        .map(UserService.extractData)
        .catch(UserService.handleError);
  }

  remove(user: User): Observable<User> {
    return this.http.delete(this.usersUrl + user.id, {})
        .map(()=> user)
        .catch(UserService.handleError);
  }

  private static extractData(res: Response) {
    return res.json();
  }

  private static handleError (error: Response | any) {
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

  get http():Http {
    return this._http;
  }

  get usersUrl(): string {
    return environment.baseUrl + this._usersUrl;
  }
}
