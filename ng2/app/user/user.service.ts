import { Injectable } from '@angular/core';
import {Http, Response, Headers, RequestOptions} from "@angular/http";
import {Observable} from "rxjs";
import {User} from "./user";
import {environment} from "../../environments/environment";

@Injectable()
export class UserService {
  private _usersUrl = 'users/';

  constructor(private _http: Http) { }

  getUsers (): Observable<User[]> {
    return this.http.get(this.usersUrl)
        .map(UserService.extractData)
        .catch(UserService.handleError);
  }

  addUser (user: User): Observable<User> {
    let headers = new Headers({ 'Content-Type': 'application/json' });
    let options = new RequestOptions({ headers: headers });
console.log(user);
    return this.http.post(this.usersUrl, user, options)
        .map(UserService.extractData)
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
