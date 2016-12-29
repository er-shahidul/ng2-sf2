import { Component, OnInit } from '@angular/core';
import {User} from "../user";
import {UserService} from "../user.service";

@Component({
  selector: 'app-user-list',
  templateUrl: './user-list.component.html',
  styleUrls: ['./user-list.component.scss']
})
export class UserListComponent implements OnInit {
  _emptyMessage= 'No Data Found';
  emptyMessage= 'Loading data';
  users: Array<User>;
  constructor(protected userService:UserService) { }

  getUsers() {
    this.emptyMessage = 'Loading data from server...';
    this.userService.getUsers()
        .subscribe(users =>{ this.users = users;  this.emptyMessage = this._emptyMessage });
  }

  removeUser(user:User) {
    if(!confirm("Are you sure, you want to delete user data?")) return;

    this.userService.remove(user)
        .subscribe(users => this.users = this.users.filter(value => value.id != user.id));
  }

  ngOnInit() {
    this.getUsers();
  }

}
