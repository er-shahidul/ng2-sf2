import {UserList} from "./user-list";
import {User} from "./user";

export class UserListResponse {
    page: number = 1;
    limit: number = 2;
    pages: number = 0;
    total: number = 0;
    _embedded: UserList = new UserList();

    get users(): User[] {
        return this._embedded.items;
    }
}