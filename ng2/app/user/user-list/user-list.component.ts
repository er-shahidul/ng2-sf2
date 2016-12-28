import { Component, OnInit } from '@angular/core';
import {User} from "../user";
import {UserService} from "../user.service";

@Component({
  selector: 'app-user-list',
  templateUrl: './user-list.component.html',
  styleUrls: ['./user-list.component.scss']
})
export class UserListComponent implements OnInit {
  users: Array<User>;
  constructor(protected userService:UserService) { }

  getUsers() {
    this.userService.getUsers()
        .subscribe(users => this.users = users);
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
