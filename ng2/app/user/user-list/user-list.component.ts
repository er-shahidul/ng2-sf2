import {Component, OnInit} from '@angular/core';
import {User} from "../user";
import {UserService} from "../user.service";
import {UserListResponse} from "../user-list-response";

@Component({
    selector: 'app-user-list',
    templateUrl: './user-list.component.html',
    styleUrls: ['./user-list.component.scss']
})
export class UserListComponent implements OnInit {
    _emptyMessage = 'No Data Found';
    emptyMessage = 'Loading data';
    response: UserListResponse = new UserListResponse();

    constructor(protected userService: UserService) {
    }

    getUsers(page = 1) {
        this.emptyMessage = 'Loading data from server...';
        this.userService.getUsers(page + "")
            .subscribe(response => {
                this.response = response;
                this.emptyMessage = this._emptyMessage
                console.log(this.response);
            });
    }

    removeUser(user: User) {
        if (!confirm("Are you sure, you want to delete user data?")) return;

        this.userService.remove(user)
            .subscribe(users => this.getUsers());
    }

    goTo(paginate) {
        console.log(paginate.page);
        this.getUsers(paginate.page)
    }

    ngOnInit() {
        this.getUsers();
    }

}
