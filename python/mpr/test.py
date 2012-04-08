#!/usr/bin/env python
import mpr

class Boo:
    def __init__(self):
        self.foo = Foo()

class Foo:
    def __init__(self):
        self.id = None
        self.test = 'test'
        self.y = [9,8,7]
        self.x = (1,2,3)
        self.z = {1:'a',2:'c',3:'d'}

    def boo(self):
        self.boo = Boo()

if __name__ == "__main__":
    foo = Foo()
    foo.boo()
    mpr.dump(foo)
